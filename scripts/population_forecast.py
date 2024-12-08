import pandas as pd
import numpy as np
from statsmodels.tsa.arima.model import ARIMA
from statsmodels.tsa.stattools import adfuller, acf
from sklearn.metrics import mean_absolute_percentage_error, mean_absolute_error, mean_squared_error
import itertools
import sys
import json
import warnings
warnings.filterwarnings('ignore')
from scipy import stats
from statsmodels.tsa.seasonal import seasonal_decompose
import pmdarima as pm

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

def preprocess_data(data):
    """Preprocess the data to handle outliers and smooth the series"""
    try:
        # Detect and handle outliers using Z-score method
        z_scores = stats.zscore(data)
        outliers_idx = np.where(np.abs(z_scores) > 3)[0]
        clean_data = data.copy()
        
        if len(outliers_idx) > 0:
            # Replace outliers with moving average
            window = min(3, len(data)-1)  # Ensure window size doesn't exceed data length
            ma = pd.Series(data).rolling(window=window, center=True, min_periods=1).mean()
            for idx in outliers_idx:
                clean_data[idx] = ma[idx]
        
        # Apply exponential smoothing to reduce noise
        alpha = 0.7  # smoothing factor
        smoothed_data = np.zeros_like(clean_data)
        smoothed_data[0] = clean_data[0]
        for i in range(1, len(clean_data)):
            smoothed_data[i] = alpha * clean_data[i] + (1 - alpha) * smoothed_data[i-1]
        
        return smoothed_data
    except Exception as e:
        # If preprocessing fails, return original data
        print(f"Preprocessing warning: {str(e)}")
        return data

def analyze_seasonality(data):
    """Analyze if the data has seasonal patterns"""
    try:
        if len(data) < 4:  # Need minimum data points for seasonality
            return False
            
        period = min(len(data)-1, 10)
        if period < 2:  # Need at least 2 periods for decomposition
            return False
            
        decomposition = seasonal_decompose(data, period=period)
        seasonal_strength = np.std(decomposition.seasonal) / np.std(data)
        return seasonal_strength > 0.1
    except:
        return False

def find_best_arima_params(data):
    """Find the best ARIMA parameters using auto_arima"""
    if len(data) < 3:  # Check minimum required data points
        raise ValueError("At least 3 data points are required for forecasting")
        
    # Preprocess the data
    processed_data = preprocess_data(data)
    
    # Check for seasonality
    has_seasonality = analyze_seasonality(processed_data)
    
    try:
        # Use auto_arima for automatic model selection
        model = pm.auto_arima(
            processed_data,
            start_p=0, start_q=0, max_p=3, max_q=3, max_d=2,  # Reduced max values
            seasonal=has_seasonality,
            m=10 if has_seasonality else 1,
            start_P=0, start_Q=0, max_P=2, max_Q=2, max_D=1,
            information_criterion='aic',
            test='adf',
            trace=False,
            error_action='ignore',
            stepwise=True,
            suppress_warnings=True,
            max_order=5  # Limit total order to prevent overfitting
        )
        
        order = model.order
        seasonal_order = model.seasonal_order if has_seasonality else (0, 0, 0, 0)
        
        # Fit SARIMA model with the found parameters
        if has_seasonality:
            final_model = ARIMA(processed_data, order=order, seasonal_order=seasonal_order).fit()
        else:
            final_model = ARIMA(processed_data, order=order).fit()
        
        return final_model, order, seasonal_order
        
    except Exception as e:
        print(f"Auto ARIMA failed: {str(e)}")
        # Fallback to simpler model
        try:
            # Test stationarity
            adf_result = adfuller(processed_data)
            d = 1 if adf_result[1] > 0.05 else 0
            
            # Check autocorrelation
            acf_vals = acf(processed_data, nlags=min(10, len(processed_data)-1))
            p = min(2, sum(np.abs(acf_vals[1:]) > 0.2))
            q = 1
            
            order = (p, d, q)
            final_model = ARIMA(processed_data, order=order).fit()
            return final_model, order, (0, 0, 0, 0)
            
        except Exception as e:
            print(f"Fallback model failed: {str(e)}")
            # Ultimate fallback to simplest model
            order = (1, 1, 0)
            final_model = ARIMA(processed_data, order=order).fit()
            return final_model, order, (0, 0, 0, 0)

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
        
        # Find best ARIMA model with improved parameter selection
        best_model, order, seasonal_order = find_best_arima_params(y)
        
        # Generate forecast with confidence intervals
        forecast = best_model.forecast(steps=forecast_years)
        forecast_ci = best_model.get_forecast(steps=forecast_years).conf_int()
        
        # Post-process forecasts
        forecast = np.maximum(forecast, 0)  # Ensure non-negative values
        forecast_ci = np.maximum(forecast_ci, 0)
        
        # Apply more sophisticated growth constraints
        historical_growth_rates = np.diff(y) / y[:-1]
        growth_std = np.std(historical_growth_rates)
        max_growth = min(0.15, np.mean(historical_growth_rates) + 2 * growth_std)
        min_growth = max(-0.1, np.mean(historical_growth_rates) - 2 * growth_std)
        
        # Smooth extreme forecasts
        for i in range(1, len(forecast)):
            growth = (forecast[i] - forecast[i-1]) / forecast[i-1]
            if growth > max_growth:
                forecast[i] = forecast[i-1] * (1 + max_growth)
            elif growth < min_growth:
                forecast[i] = forecast[i-1] * (1 + min_growth)
        
        # Calculate metrics
        metrics = calculate_forecast_metrics(y, best_model.fittedvalues)
        metrics['model_type'] = f'ARIMA{order}'
        
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
