// Function to create Energy Consumption Gauge
function createEnergyConsumptionGauge(containerId, value) {
    Plotly.newPlot(containerId, [{
        type: 'indicator',
        mode: 'gauge+number',
        value: value,
        title: { text: "Units Consumed", font: { size: 20, color: 'grey' } },
        number: {
            font: { size: 35 }, valueformat: ".3f",
            suffix: "<span style='font-size:12px;'>kWh</span>"
        },

        gauge: {
            axis: { range: [0, 150], tickfont: { size: 15 } },
            steps: [
                { range: [0, 50], color: "green" },
                { range: [50, 100], color: "green" },
                { range: [1000, 150], color: "green" }
            ],
            bar: { color: "#f6921e", thickness: 0.5 }
        }
    }], { responsive: true, height: 220, margin: { t: 40, b: 20, l: 20, r: 40 } });
}

// Function to create Remaining Units Gauge
function createRemainingUnitsGauge(containerId, value) {
    Plotly.newPlot(containerId, [{
        type: 'indicator',
        mode: 'gauge+number',
        value: value,
        title: { text: "Remaining Units", font: { size: 20, color: 'grey' } },
        number: {
            font: { size: 35 }, valueformat: ".3f",
            suffix: "<span style='font-size:12px;'>kWh</span>"
        },
        gauge: {
            axis: { range: [0, 150], tickfont: { size: 15 } },
            steps: [
                { range: [0, 50], color: "green" },
                { range: [50, 100], color: "green" },
                { range: [100, 150], color: "green" }
            ],
            bar: { color: "#f6921e", thickness: 0.5 }
        }
    }], { responsive: true, height: 220, margin: { t: 40, b: 20, l: 20, r: 40 } });
}

// Fetch data and initialize widgets
async function fetchAndInitializeDashboardWidgets() {
    try {
        const response = await fetch('../pages/energy_data.php'); // Your PHP script path
        const data = await response.json();

        // Check for errors
        if (data.error) {
            console.error(data.error);
            // Handle error scenario (e.g., show message to user)
            document.getElementById('energyConsumptionGauge').innerHTML = 'No room data available';
            document.getElementById('sensorGaugeFront').innerHTML = 'No room data available';
            return;
        }

        // Create gauges
        createEnergyConsumptionGauge('energyConsumptionGauge', parseFloat(data.energy_consumed) || 0);
        createRemainingUnitsGauge('sensorGaugeFront', parseFloat(data.remaining_units) || 0);
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
        // Fallback to default values if fetch fails
        createEnergyConsumptionGauge('energyConsumptionGauge', 0);
        createRemainingUnitsGauge('sensorGaugeFront', 0);
    }
}

// Initialize everything when the page loads
document.addEventListener('DOMContentLoaded', fetchAndInitializeDashboardWidgets);

// Function to plot the energy usage chart for each room
async function fetchAndPlotEnergyUsage() {
    try {
        const response = await fetch('../pages/energy_data.php'); // Fetch energy data from PHP script
        const data = await response.json();

        // Check if the data contains an error
        if (data.error) {
            console.error(data.error);
            document.getElementById('hourlyUsageChart').innerHTML = 'No room data available';
            return;
        }

        const roomData = {
            time: [],
            energyConsumed: []
        };

        // For example, here we assume data contains 'new_consumed' at different times.
        // Populate room data for plotting.
        roomData.time.push(data.last_updated);
        roomData.energyConsumed.push(data.new_consumed);

        // Create the line chart for energy usage breakdown
        const trace = {
            x: roomData.time,
            y: roomData.energyConsumed,
            mode: 'lines+markers',
            name: 'Energy Consumed',
            line: { shape: 'linear', color: 'blue' },
            marker: { color: 'blue', size: 6 }
        };

        const layout = {
            title: 'Energy Consumption Over Time',
            xaxis: { title: 'Time' },
            yaxis: { title: 'Energy Consumed (kWh)' },
            showlegend: false,
        };

        // Plot the graph inside the specified container
        Plotly.newPlot('dailyEnergyChart2', [trace], layout);

    } catch (error) {
        console.error('Error fetching data for energy usage chart:', error);
    }
}

// Fetch data every 2 minutes (120000ms)
setInterval(fetchAndPlotEnergyUsage, 120000);

// Initialize chart on page load
document.addEventListener('DOMContentLoaded', fetchAndPlotEnergyUsage);
