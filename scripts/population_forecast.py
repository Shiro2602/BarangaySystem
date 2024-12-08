import pandas as pd
import numpy as np
from statsmodels.tsa.arima.model import ARIMA
from statsmodels.tsa.stattools import adfuller
from sklearn.metrics import mean_absolute_percentage_error, mean_absolute_error, mean_squared_error
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

def interpolate_missing_years(df):
    """Interpolate missing years in the dataset"""
    try:
        # Create a complete year range
        min_year = df['year'].min()
        max_year = df['year'].max()
        full_range = pd.DataFrame({'year': range(int(min_year), int(max_year) + 1)})
        
        # Merge with existing data
        df_interpolated = pd.merge(full_range, df, on='year', how='left')
        
        # Linear interpolation for missing values
        df_interpolated['population'] = df_interpolated['population'].interpolate(
            method='linear',
            limit_direction='both'
        )
        
        # Handle any remaining NaN values at edges
        if df_interpolated['population'].isna().any():
            first_valid = df_interpolated['population'].first_valid_index()
            last_valid = df_interpolated['population'].last_valid_index()
            
            # Forward fill any leading NaN
            if first_valid > 0:
                df_interpolated.loc[:first_valid, 'population'] = df_interpolated.loc[first_valid, 'population']
            
            # Backward fill any trailing NaN
            if last_valid < len(df_interpolated) - 1:
                df_interpolated.loc[last_valid:, 'population'] = df_interpolated.loc[last_valid, 'population']
        
        # Round to integers
        df_interpolated['population'] = df_interpolated['population'].round().astype(int)
        
        return df_interpolated
        
    except Exception as e:
        print(f"Interpolation warning: {str(e)}")
        return df

def preprocess_data(data, years=None):
    """Preprocess the data to handle outliers and ensure stationarity"""
    try:
        series = pd.Series(data)
        
        # If years are provided, handle gap years first
        if years is not None:
            temp_df = pd.DataFrame({'year': years, 'population': series})
            temp_df = interpolate_missing_years(temp_df)
            series = temp_df['population']
        
        # Outlier detection using modified z-score
        median = np.median(series)
        mad = np.median(np.abs(series - median))
        if mad == 0:
            mad = 1  # Prevent division by zero
        modified_z_scores = 0.6745 * (series - median) / mad
        
        clean_data = series.copy()
        outliers_mask = np.abs(modified_z_scores) > 3.5  # Threshold
        
        if outliers_mask.any():
            clean_data[outliers_mask] = np.nan
            clean_data = clean_data.interpolate(method='linear', limit_direction='both')
        
        # Ensure stationarity by differencing
        differenced = clean_data.diff().dropna()
        
        return differenced.values, clean_data.values
    
    except Exception as e:
        print(f"Preprocessing warning: {str(e)}")
        return data, data

def find_best_arima_params(differenced_data, original_data):
    """Find the best ARIMA parameters using grid search based on AIC"""
    best_aic = float('inf')
    best_order = None
    best_model = None
    
    # Define the p, d, q ranges
    p_values = range(0, 4)
    d_values = range(0, 2)  # Typically 0 or 1
    q_values = range(0, 4)
    
    for p in p_values:
        for d in d_values:
            for q in q_values:
                try:
                    model = ARIMA(original_data, order=(p, d, q))
                    results = model.fit()
                    if results.aic < best_aic:
                        best_aic = results.aic
                        best_order = (p, d, q)
                        best_model = results
                except:
                    continue
    
    if best_model is None:
        # Fallback to (1,1,1)
        best_order = (1, 1, 1)
        best_model = ARIMA(original_data, order=best_order).fit()
    
    return best_model, best_order

def perform_forecast(data_file, forecast_years=5):
    try:
        # Read the CSV file
        df = pd.read_csv(data_file)
        
        # Basic validation
        required_columns = ['year', 'population']
        if not all(col in df.columns for col in required_columns):
            return json.dumps({
                'error': 'CSV file must contain "year" and "population" columns'
            })
    
        # Convert to numeric and handle any non-numeric values
        df['year'] = pd.to_numeric(df['year'], errors='coerce')
        df['population'] = pd.to_numeric(df['population'], errors='coerce')
        df = df.dropna()
        
        # Sort and remove duplicates
        df = df.sort_values('year').drop_duplicates('year')
        
        # Validate minimum data points
        if len(df) < 5:
            return json.dumps({
                'error': 'At least 5 years of historical data required for better forecasting'
            })
        
        # Handle gap years
        min_year = int(df['year'].min())
        max_year = int(df['year'].max())
        expected_years = set(range(min_year, max_year + 1))
        actual_years = set(df['year'].astype(int).values)
        
        if len(expected_years - actual_years) > 0:
            print(f"Detected gaps in years. Interpolating missing data...")
            df = interpolate_missing_years(df)
        
        # Prepare data for ARIMA
        y = df['population'].values
        years = df['year'].values
        
        # Preprocess data to ensure stationarity
        differenced_data, original_data = preprocess_data(y, years)
        
        # Find best ARIMA model
        best_model, order = find_best_arima_params(differenced_data, y)
        
        # Generate forecast
        forecast = best_model.forecast(steps=forecast_years)
        forecast_ci = best_model.get_forecast(steps=forecast_years).conf_int()
        
        # Apply constraints to prevent unrealistic forecasts
        last_value = y[-1]
        min_growth = -0.02  # Max 2% decline per year
        max_growth = 0.05   # Max 5% growth per year
        
        for i in range(len(forecast)):
            min_value = last_value * (1 + min_growth)
            max_value = last_value * (1 + max_growth)
            forecast[i] = np.clip(forecast[i], min_value, max_value)
            forecast_ci[i] = np.clip(forecast_ci[i], min_value, max_value)
            last_value = forecast[i]
        
        # Ensure monotonic trend if historical data shows consistent growth
        if np.all(np.diff(y[-5:]) > 0):  # Check last 5 years for increasing trend
            for i in range(1, len(forecast)):
                if forecast[i] < forecast[i-1]:
                    forecast[i] = forecast[i-1]
                if forecast_ci[i][0] < forecast_ci[i-1][0]:
                    forecast_ci[i][0] = forecast_ci[i-1][0]
                if forecast_ci[i][1] < forecast_ci[i-1][1]:
                    forecast_ci[i][1] = forecast_ci[i-1][1]
        
        # Round values
        forecast = np.round(forecast).astype(int)
        forecast_ci = np.round(forecast_ci).astype(int)
        
        # Create forecast dataframe
        future_years = range(int(df['year'].max()) + 1, int(df['year'].max()) + forecast_years + 1)
        forecast_df = pd.DataFrame({
            'year': future_years,
            'population': forecast,
            'lower_ci': forecast_ci[:, 0],
            'upper_ci': forecast_ci[:, 1]
        })
        
        # Calculate metrics
        metrics = calculate_forecast_metrics(y, best_model.fittedvalues)
        metrics['model_type'] = f'ARIMA{order}'
        
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