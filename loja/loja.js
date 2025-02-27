function updateDashboardChart() {
    fetch('vendas.php')
        .then(response => response.json())
        .then(data => {
            // Update your existing chart with new data
            window.salesChart.data.datasets[0].data = data;
            window.salesChart.update();
        });
}