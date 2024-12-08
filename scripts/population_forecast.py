import pandas as pd
import numpy as np
from statsmodels.tsa.statespace.sarimax import SARIMAX
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
    
    # Theil's U statistic
    naive_forecast = np.roll(actual, 1)[1:]
    actual_changes = actual[1:]
    mse_model = np.mean((actual_changes - predicted[:-1])**2)
    mse_naive = np.mean((actual_changes - naive_forecast)**2)
    metrics['theils_u'] = round(np.sqrt(mse_model / mse_naive), 2)
    
    return metrics

def find_best_sarima_params(data):
    """Find the best SARIMA parameters using grid search"""
    # Calculate growth rate and trend strength
    growth_rates = np.diff(data) / data[:-1]
    trend_strength = np.abs(np.mean(growth_rates))
    
    # Test stationarity
    adf_result = adfuller(data)
    is_stationary = adf_result[1] < 0.05
    
    # Set parameter ranges
    if is_stationary:
        p_range = range(0, 3)
        d_range = [0]
        q_range = range(0, 3)
    else:
        p_range = range(0, 3)
        d_range = [1]
        q_range = range(0, 3)
    
    # Seasonal parameters
    P_range = range(0, 2)
    D_range = [0]
    Q_range = range(0, 2)
    s = 3  # Try different seasonal periods (3-5 years is common for population data)
    
    best_aic = float('inf')
    best_model = None
    best_order = None
    best_seasonal_order = None
    
    try:
        # Grid search for both regular and seasonal parameters
        for p, d, q in itertools.product(p_range, d_range, q_range):
            for P, D, Q in itertools.product(P_range, D_range, Q_range):
                if p + d + q + P + D + Q <= 6:  # Limit model complexity
                    try:
                        model = SARIMAX(
                            data,
                            order=(p, d, q),
                            seasonal_order=(P, D, Q, s),
                            enforce_stationarity=False,
                            enforce_invertibility=False
                        ).fit(disp=False)
                        
                        if model.aic < best_aic:
                            best_aic = model.aic
                            best_model = model
                            best_order = (p, d, q)
                            best_seasonal_order = (P, D, Q, s)
                    except:
                        continue
        
        if best_model is None:
            # Fallback to simple model
            best_model = SARIMAX(
                data,
                order=(1, 1 if not is_stationary else 0, 1),
                seasonal_order=(1, 0, 1, s)
            ).fit(disp=False)
            best_order = (1, 1 if not is_stationary else 0, 1)
            best_seasonal_order = (1, 0, 1, s)
        
        return best_model, best_order, best_seasonal_order
        
    except Exception as e:
        # Ultimate fallback
        model = SARIMAX(data, order=(1, 0, 0), seasonal_order=(0, 0, 0, s)).fit(disp=False)
        return model, (1, 0, 0), (0, 0, 0, s)

def apply_population_dynamics(forecast, historical_data):
    """Apply realistic population dynamics constraints"""
    
    # Calculate historical statistics
    historical_mean = np.mean(historical_data)
    historical_std = np.std(historical_data)
    
    # Define reasonable bounds
    upper_bound = historical_mean + 2 * historical_std
    lower_bound = max(historical_mean - 2 * historical_std, historical_data[-1] * 0.8)
    
    # Apply bounds with smoothing
    constrained_forecast = np.copy(forecast)
    for i in range(len(forecast)):
        # Gradually tighten bounds over time
        time_factor = (i + 1) / len(forecast)
        current_upper = historical_data[-1] + (upper_bound - historical_data[-1]) * (1 - time_factor * 0.5)
        current_lower = historical_data[-1] + (lower_bound - historical_data[-1]) * (1 - time_factor * 0.5)
        
        # Apply constraints
        constrained_forecast[i] = np.clip(forecast[i], current_lower, current_upper)
    
    return constrained_forecast

def perform_forecast(data_file, forecast_years=5):
    try:
        # Read the CSV file
        df = pd.read_csv(data_file)
        
        # Validation checks
        required_columns = ['year', 'population']
        if not all(col in df.columns for col in required_columns):
            return json.dumps({
                'error': 'CSV file must contain "year" and "population" columns'
            })

        # Data preprocessing
        df['population'] = pd.to_numeric(df['population'], errors='coerce')
        df = df.dropna()
        df = df.sort_values('year')
        
        if len(df) < 4:
            return json.dumps({
                'error': 'Insufficient data points for forecasting (minimum 4 required)'
            })
            
        # Handle gaps in years
        full_year_range = pd.DataFrame({'year': range(df['year'].min(), df['year'].max() + 1)})
        df = pd.merge(full_year_range, df, on='year', how='left')
        df['population'] = df['population'].interpolate(method='linear')
        
        # Prepare data
        y = df['population'].values
        
        # Calculate historical statistics
        historical_growth = np.mean(np.diff(y) / y[:-1])
        historical_volatility = np.std(np.diff(y) / y[:-1])
        
        # Find best SARIMA model
        best_model, best_order, best_seasonal_order = find_best_sarima_params(y)
        
        # Generate forecasts
        forecast = best_model.forecast(steps=forecast_years)
        
        # Apply population dynamics constraints
        forecast = apply_population_dynamics(forecast, y)
        
        # Generate confidence intervals
        forecast_std_err = np.sqrt(best_model.cov_params().diagonal())
        z_score = 1.96  # 95% confidence interval
        
        # Calculate confidence intervals
        forecast_ci_lower = forecast - (z_score * forecast_std_err[0])
        forecast_ci_upper = forecast + (z_score * forecast_std_err[0])
        
        # Ensure forecasts and CIs are positive and reasonable
        forecast = np.maximum(forecast, y[-1] * 0.5)
        forecast_ci_lower = np.maximum(forecast_ci_lower, y[-1] * 0.5)
        forecast_ci_upper = np.maximum(forecast_ci_upper, forecast)
        
        # Prepare forecast data
        last_year = df['year'].max()
        future_years = range(last_year + 1, last_year + forecast_years + 1)
        forecast_df = pd.DataFrame({
            'year': future_years,
            'population': forecast.round().astype(int),
            'lower_ci': forecast_ci_lower.round().astype(int),
            'upper_ci': forecast_ci_upper.round().astype(int)
        })
        
        # Calculate metrics
        metrics = calculate_forecast_metrics(y, best_model.fittedvalues)
        metrics['model_type'] = f'SARIMA{best_order}x{best_seasonal_order}'
        
        # Growth rates and trend analysis
        total_growth = (forecast[-1] - y[-1]) / y[-1] * 100
        avg_annual_growth = (((forecast[-1] / y[-1]) ** (1/forecast_years)) - 1) * 100
        metrics['total_growth_percent'] = round(total_growth, 2)
        metrics['avg_annual_growth_percent'] = round(avg_annual_growth, 2)
        metrics['trend'] = 'Increasing' if total_growth > 0 else 'Decreasing' if total_growth < 0 else 'Stable'
        metrics['forecast_reliability'] = 'High' if metrics['mape'] < 10 else 'Medium' if metrics['mape'] < 20 else 'Low'
        
        # Add warning if interpolation was used
        gaps_filled = len(full_year_range) - len(df['year'].unique())
        if gaps_filled > 0:
            metrics['warnings'] = f'Filled {gaps_filled} missing years with linear interpolation'
            
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
