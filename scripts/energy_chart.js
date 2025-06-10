let timeSeriesData = {
    time: [],
    energyConsumed: []
};

// Function to plot the energy usage chart for each room
async function fetchAndPlotEnergyUsage() {
    try {
        const response = await fetch('energy_data.php'); // Fetch energy data from PHP script
        const data = await response.json();

        // Check if the data contains an error
        if (data.error) {
            console.error(data.error);
            document.getElementById('hourlyUsageChart').innerHTML = '<p style="color:red;">No room data available</p>';
            return;
        }

        // Append new data points while maintaining previous points
        data.energy_data.forEach(entry => {
            // Prevent duplicate entries in time series
            const existingIndex = timeSeriesData.time.indexOf(entry.timestamp);
            if (existingIndex === -1) {
                timeSeriesData.time.push(entry.timestamp); // Use the full timestamp for precision
                timeSeriesData.energyConsumed.push(parseFloat(entry.new_consumed)); // Store energy consumption
            }
        });

        // Create the line chart for energy usage breakdown
        const trace = {
            x: timeSeriesData.time,
            y: timeSeriesData.energyConsumed,
            mode: 'lines+markers',
            name: 'Energy Consumed',
            line: { shape: 'linear', color: 'blue' },
            marker: { color: 'blue', size: 6 }
        };

        const layout = {
            title: 'Energy Consumption Over the Last 24 Hours',
            xaxis: { 
                title: 'Time of Day', 
                tickangle: -45,
                tickvals: timeSeriesData.time, // Ensure all timestamps are displayed on the x-axis
                ticktext: timeSeriesData.time.map(time => {
                    const date = new Date(time);
                    return date.getHours() + ':' + String(date.getMinutes()).padStart(2, '0'); // Format the time nicely
                })
            },
            yaxis: { title: 'Energy Consumed (kWh)' },
            showlegend: false,
            margin: { t: 40, b: 60, l: 60, r: 40 },
        };

        // Plot the graph inside the specified container
        Plotly.react('dailyEnergyChart2', [trace], layout);

    } catch (error) {
        console.error('Error fetching data for energy usage chart:', error);
    }
}

// Fetch data every 2 minutes (120000ms)
setInterval(fetchAndPlotEnergyUsage, 120000);

// Initialize chart on page load
document.addEventListener('DOMContentLoaded', fetchAndPlotEnergyUsage);
