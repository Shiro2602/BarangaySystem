import pandas as pd
import numpy as np
from statsmodels.tsa.arima.model import ARIMA
from statsmodels.tsa.stattools import adfuller
from sklearn.metrics import mean_absolute_percentage_error, mean_absolute_error, mean_squared_error
import itertools
import sys
import json
import warnings
warnings.filterwarnings('ignore')

def calculate_forecast_metrics(actual, predicted):
    """Calculate comprehensive forecast metrics"""
    metrics = {}
    
    # Basic error metrics
    metrics['mape'] = round(mean_absolute_percentage_error(actual, predicted) * 100, 2)
    metrics['mae'] = round(mean_absolute_error(actual, predicted), 2)
    metrics['rmse'] = round(np.sqrt(mean_squared_error(actual, predicted)), 2)
    
    # Tracking metrics
    metrics['tracking_signal'] = round(np.sum(actual - predicted) / (len(actual) * metrics['mae']), 2)
    
    # Theil's U statistic (compare with naive forecast)
    naive_forecast = np.roll(actual, 1)[1:]
    actual_changes = actual[1:]
    mse_model = np.mean((actual_changes - predicted[:-1])**2)
    mse_naive = np.mean((actual_changes - naive_forecast)**2)
    metrics['theils_u'] = round(np.sqrt(mse_model / mse_naive), 2)
    
    return metrics

def find_best_arima_params(data):
    """Find the best ARIMA parameters using grid search and cross-validation"""
    best_aic = float('inf')
    best_order = None
    best_model = None
    
    # Calculate growth rate for trend analysis
    growth_rates = np.diff(data) / data[:-1]
    trend_strength = np.abs(np.mean(growth_rates))
    
    # Adjust parameter ranges based on data characteristics
    if trend_strength > 0.1:  # Strong trend
        p_values = range(0, 3)
        d_values = [1]  # Force differencing for strong trends
        q_values = range(0, 3)
    else:  # Weak or no trend
        p_values = range(0, 4)
        d_values = [0, 1]
        q_values = range(0, 3)
    
    # Test stationarity
    adf_result = adfuller(data)
    if adf_result[1] > 0.05:  # Non-stationary
        d_values = [1]  # Force differencing
    
    # Grid search
    for p in p_values:
        for d in d_values:
            for q in q_values:
                try:
                    model = ARIMA(data, order=(p, d, q))
                    results = model.fit()
                    
                    # Calculate AIC and add penalty for complexity
                    aic = results.aic
                    complexity_penalty = (p + q) * 0.1 * len(data)  # Penalty increases with data size
                    adjusted_aic = aic + complexity_penalty
                    
                    if adjusted_aic < best_aic:
                        best_aic = adjusted_aic
                        best_order = (p, d, q)
                        best_model = results
                        
                except:
                    continue
    
    # If no good model found, use simple models based on trend strength
    if best_model is None:
        try:
            if trend_strength > 0.05:
                best_order = (1, 1, 1)
            else:
                best_order = (1, 0, 1)
            best_model = ARIMA(data, order=best_order).fit()
        except:
            best_order = (1, 0, 0)
            best_model = ARIMA(data, order=best_order).fit()
    
    return best_model, best_order

def perform_forecast(data_file, forecast_years=5):
    try:
        # Read the CSV file
        df = pd.read_csv(data_file)
        
        # Ensure the dataframe has 'year' and 'population' columns
        required_columns = ['year', 'population']
        if not all(col in df.columns for col in required_columns):
            return json.dumps({
                'error': 'CSV file must contain "year" and "population" columns'
            })

        # Convert to numeric and handle any non-numeric values
        df['population'] = pd.to_numeric(df['population'], errors='coerce')
        df = df.dropna()
        df = df.sort_values('year')
        
        # Prepare data for ARIMA
        y = df['population'].values
        
        # Find best ARIMA model
        best_model, best_params = find_best_arima_params(y)
        
        # Generate forecast with confidence intervals
        forecast = best_model.forecast(steps=forecast_years)
        forecast_ci = best_model.get_forecast(steps=forecast_years).conf_int()
        
        # Ensure forecasts are positive
        forecast = np.maximum(forecast, 0)
        forecast_ci = np.maximum(forecast_ci, 0)
        
        # Apply growth rate constraints
        historical_growth = np.mean(np.diff(y) / y[:-1])
        max_growth = max(0.15, historical_growth * 2)  # Cap maximum growth rate
        min_growth = min(-0.1, historical_growth / 2)  # Cap minimum growth rate
        
        # Adjust forecasts based on growth constraints
        for i in range(1, len(forecast)):
            growth = (forecast[i] - forecast[i-1]) / forecast[i-1]
            if growth > max_growth:
                forecast[i] = forecast[i-1] * (1 + max_growth)
            elif growth < min_growth:
                forecast[i] = forecast[i-1] * (1 + min_growth)
        
        # Calculate metrics
        metrics = calculate_forecast_metrics(y, best_model.fittedvalues)
        metrics['model_type'] = f'ARIMA{best_params}'
        
        # Calculate growth rates
        total_growth = (forecast[-1] - y[-1]) / y[-1] * 100
        avg_annual_growth = (((forecast[-1] / y[-1]) ** (1/forecast_years)) - 1) * 100
        metrics['total_growth_percent'] = round(total_growth, 2)
        metrics['avg_annual_growth_percent'] = round(avg_annual_growth, 2)
        
        # Prepare forecast data with confidence intervals
        last_year = df['year'].max()
        future_years = range(last_year + 1, last_year + forecast_years + 1)
        forecast_df = pd.DataFrame({
            'year': future_years,
            'population': forecast.round().astype(int),
            'lower_ci': forecast_ci[:, 0].round().astype(int),
            'upper_ci': forecast_ci[:, 1].round().astype(int)
        })
        
        # Add trend analysis
        metrics['trend'] = 'Increasing' if total_growth > 0 else 'Decreasing' if total_growth < 0 else 'Stable'
        metrics['forecast_reliability'] = 'High' if metrics['mape'] < 10 else 'Medium' if metrics['mape'] < 20 else 'Low'
        
        # Prepare response
        result = {
            'historical': df[['year', 'population']].to_dict('records'),
            'forecast': forecast_df.to_dict('records'),
            'metrics': metrics
        }
        
        return json.dumps(result)
        
    except Exception as e:
        return json.dumps({
            'error': str(e)
        })

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No input file provided'}))
    else:
        data_file = sys.argv[1]
        forecast_years = int(sys.argv[2]) if len(sys.argv) > 2 else 5
        print(perform_forecast(data_file, forecast_years))
