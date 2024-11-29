import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import PolynomialFeatures
from sklearn.metrics import mean_absolute_percentage_error
import sys
import json

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

        # Prepare data for modeling
        X = df['year'].values.reshape(-1, 1)
        y = df['population'].values

        # Create and train linear model
        linear_model = LinearRegression()
        linear_model.fit(X, y)

        # Create and train polynomial model (degree 2)
        poly_features = PolynomialFeatures(degree=2)
        X_poly = poly_features.fit_transform(X)
        poly_model = LinearRegression()
        poly_model.fit(X_poly, y)

        # Generate future years
        last_year = df['year'].max()
        future_years = np.array(range(last_year + 1, last_year + forecast_years + 1))
        future_X = future_years.reshape(-1, 1)
        future_X_poly = poly_features.transform(future_X)

        # Make predictions
        linear_forecast = linear_model.predict(future_X)
        poly_forecast = poly_model.predict(future_X_poly)

        # Choose the better model based on MAPE
        linear_mape = mean_absolute_percentage_error(y, linear_model.predict(X))
        poly_mape = mean_absolute_percentage_error(y, poly_model.predict(X_poly))

        if linear_mape <= poly_mape:
            forecast = linear_forecast
            model_type = "Linear"
            mape = linear_mape
        else:
            forecast = poly_forecast
            model_type = "Polynomial"
            mape = poly_mape

        # Create forecast DataFrame
        forecast_df = pd.DataFrame({
            'year': future_years,
            'population': forecast.round().astype(int)
        })

        # Calculate growth rate
        total_growth = (forecast[-1] - y[-1]) / y[-1] * 100
        avg_annual_growth = (((forecast[-1] / y[-1]) ** (1/forecast_years)) - 1) * 100

        # Prepare response
        result = {
            'historical': df[['year', 'population']].to_dict('records'),
            'forecast': forecast_df.to_dict('records'),
            'metrics': {
                'mape': round(mape * 100, 2),
                'model_type': model_type,
                'total_growth_percent': round(total_growth, 2),
                'avg_annual_growth_percent': round(avg_annual_growth, 2)
            }
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
