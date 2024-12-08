import pandas as pd
import numpy as np
from statsmodels.tsa.arima.model import ARIMA
from statsmodels.tsa.stattools import adfuller
from sklearn.metrics import mean_absolute_percentage_error, mean_absolute_error, mean_squared_error
import itertools
import sys
import json
import warnings
import pmdarima as pm  # Add this import for auto_arima functionality
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
    """Find the best ARIMA parameters using a more robust approach"""
    # Calculate growth rate and trend strength (keep existing code)
    growth_rates = np.diff(data) / data[:-1]
    trend_strength = np.abs(np.mean(growth_rates))
    
    # Test stationarity
    adf_result = adfuller(data)
    is_stationary = adf_result[1] < 0.05
    
    # Use auto_arima for intelligent parameter selection
    try:
        # Set parameter ranges based on data characteristics
        max_p = 3 if trend_strength > 0.1 else 4
        max_d = 1 if not is_stationary else 0
        max_q = 3
        
        model = pm.auto_arima(
            data,
            start_p=0, max_p=max_p,
            start_q=0, max_q=max_q,
            d=max_d,
            seasonal=False,  # Set to True if dealing with seasonal data
            stepwise=True,
            suppress_warnings=True,
            error_action='ignore',
            max_order=6,  # Limit total parameters
            information_criterion='aic',
            cv=3  # Use cross-validation
        )
        
        return model, model.order
        
    except Exception as e:
        # Fallback to simple models if auto_arima fails
        fallback_order = (1, 1, 1) if trend_strength > 0.05 else (1, 0, 1)
        try:
            model = ARIMA(data, order=fallback_order).fit()
            return model, fallback_order
        except:
            # Ultimate fallback to simplest model
            model = ARIMA(data, order=(1, 0, 0)).fit()
            return model, (1, 0, 0)

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
        
        # Add data validation
        if len(df) < 4:
            return json.dumps({
                'error': 'Insufficient data points for forecasting (minimum 4 required)'
            })
            
        # Check for gaps in years
        years = df['year'].values
        if np.any(np.diff(years) != 1):
            return json.dumps({
                'error': 'Data must contain consecutive years without gaps'
            })
        
        # Prepare data for ARIMA
        y = df['population'].values
        
        # Find best ARIMA model
        best_model, best_params = find_best_arima_params(y)
        
        # Generate multiple forecasts and confidence intervals
        n_simulations = 100
        forecasts = np.zeros((n_simulations, forecast_years))
        
        for i in range(n_simulations):
            # Generate forecast with random variation
            base_forecast = best_model.simulate(nsimulations=forecast_years, anchor='end')
            
            # Apply growth constraints with some randomness
            last_value = y[-1]
            for j in range(len(base_forecast)):
                # Add controlled randomness to growth limits
                random_factor = np.random.normal(1, 0.2)  # 20% variation
                if abs(trend_info['trend_consistency']) > 0.7:
                    # Strong trend - allow it to continue but with dampening
                    max_growth = min(0.1, historical_growth * 1.5) * random_factor
                    min_growth = max(-0.1, historical_growth * 0.5) * random_factor
                else:
                    # Weak or inconsistent trend - more conservative constraints
                    max_growth = min(0.05, abs(historical_growth) + historical_volatility) * random_factor
                    min_growth = max(-0.05, -abs(historical_growth) - historical_volatility) * random_factor
                
                # Calculate growth rate
                growth = (base_forecast[j] - last_value) / last_value
                
                # Apply constraints with smoothing
                if growth > max_growth:
                    base_forecast[j] = last_value * (1 + max_growth)
                elif growth < min_growth:
                    base_forecast[j] = last_value * (1 + min_growth)
                
                # Add small random variation
                variation = np.random.normal(0, historical_volatility * 0.5)
                base_forecast[j] *= (1 + variation)
                
                last_value = base_forecast[j]
            
            forecasts[i] = base_forecast
        
        # Calculate final forecast as median of simulations
        forecast = np.median(forecasts, axis=0)
        forecast_ci = np.percentile(forecasts, [5, 95], axis=0).T
        
        # Ensure forecasts are positive and reasonable
        forecast = np.maximum(forecast, y[-1] * 0.5)  # Don't allow drops below 50% of last value
        forecast_ci = np.maximum(forecast_ci, y[-1] * 0.5)
        
        # Prepare forecast data with confidence intervals
        last_year = df['year'].max()
        future_years = range(last_year + 1, last_year + forecast_years + 1)
        forecast_df = pd.DataFrame({
            'year': future_years,
            'population': forecast.round().astype(int),
            'lower_ci': forecast_ci[:, 0].round().astype(int),
            'upper_ci': forecast_ci[:, 1].round().astype(int)
        })
        
        # Calculate metrics
        metrics = calculate_forecast_metrics(y, best_model.fittedvalues)
        metrics['model_type'] = f'ARIMA{best_params}'
        
        # Calculate growth rates
        total_growth = (forecast[-1] - y[-1]) / y[-1] * 100
        avg_annual_growth = (((forecast[-1] / y[-1]) ** (1/forecast_years)) - 1) * 100
        metrics['total_growth_percent'] = round(total_growth, 2)
        metrics['avg_annual_growth_percent'] = round(avg_annual_growth, 2)
        
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
