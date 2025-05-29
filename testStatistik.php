<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Grafik Penjualan</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
  <style>
    body {
      font-family: Arial;
      padding: 40px;
      background: #f7f7f7;
    }
    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
    }
    canvas {
      margin-top: 30px;
    }
    .summary {
      margin-top: 30px;
      background: #f9f9f9;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #3498db;
    }
    .summary h3 {
      margin-top: 0;
      color: #333;
    }
    .data-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .data-label {
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Grafik Penjualan & Pendapatan</h2>
    <div id="error-message" style="color: red; text-align: center; display: none;">
      Gagal memuat data. Silakan periksa koneksi database.
    </div>
    <canvas id="salesChart" height="100"></canvas>
    
    <div class="summary">
      <h3>Ringkasan Penjualan</h3>
      <div id="summary-data">
        <p>Memuat data...</p>
      </div>
    </div>
  </div>

  <script>
    // Fungsi untuk memformat angka ke format rupiah
    function formatRupiah(angka) {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(angka);
    }
    
    // Data statis untuk testing jika API gagal
    const fallbackData = [
      {name: 'Hari Ini', sales: 10, revenue: 1500000},
      {name: 'Kemarin', sales: 8, revenue: 1200000},
      {name: 'Minggu Lalu', sales: 45, revenue: 6700000},
      {name: 'Bulan Lalu', sales: 180, revenue: 25000000},
      {name: 'Total', sales: 243, revenue: 34400000}
    ];
    
    function renderChart(data) {
      const labels = data.map(d => d.name);
      const sales = data.map(d => d.sales);
      const revenue = data.map(d => d.revenue);
      
      // Render chart
      const ctx = document.getElementById('salesChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Jumlah Transaksi',
              data: sales,
              backgroundColor: 'rgba(54, 162, 235, 0.6)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1,
              yAxisID: 'y'
            },
            {
              label: 'Pendapatan (Rp)',
              data: revenue,
              backgroundColor: 'rgba(255, 206, 86, 0.6)',
              borderColor: 'rgba(255, 206, 86, 1)',
              borderWidth: 1,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              position: 'left',
              title: {
                display: true,
                text: 'Jumlah Transaksi'
              }
            },
            y1: {
              beginAtZero: true,
              position: 'right',
              title: {
                display: true,
                text: 'Pendapatan (Rp)'
              },
              grid: {
                drawOnChartArea: false
              }
            }
          }
        }
      });
      
      // Render summary
      const summaryDiv = document.getElementById('summary-data');
      let summaryHTML = '';
      
      // Find total data
      const totalData = data.find(item => item.name === 'Total') || 
                         {name: 'Total', sales: 0, revenue: 0};
      
      summaryHTML += `
        <div class="data-row">
          <span class="data-label">Total Transaksi:</span>
          <span>${totalData.sales}</span>
        </div>
        <div class="data-row">
          <span class="data-label">Total Pendapatan:</span>
          <span>${formatRupiah(totalData.revenue)}</span>
        </div>
      `;
      
      // Tampilkan juga detail per periode
      data.forEach(item => {
        if (item.name !== 'Total') {
          summaryHTML += `
            <div class="data-row">
              <span class="data-label">${item.name}:</span>
              <span>${item.sales} transaksi (${formatRupiah(item.revenue)})</span>
            </div>
          `;
        }
      });
      
      summaryDiv.innerHTML = summaryHTML;
    }
    
    // Ambil data dari server
    fetch('sales_data.php')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data && data.length > 0) {
          renderChart(data);
        } else {
          throw new Error('Empty data received');
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        document.getElementById('error-message').style.display = 'block';
        // Gunakan data fallback untuk testing
        renderChart(fallbackData);
      });
  </script>
  <p>test</p>
</body>
</html>