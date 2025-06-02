document.addEventListener('DOMContentLoaded', function () {
    // Register plugins
    if (typeof Chart !== 'undefined' && Chart.register) {
        Chart.register(ChartDataLabels);
    }

    // DOM Elements
    const periodButtons = document.querySelectorAll('.time-period-selector .btn');
    const chartTypeFilter = document.getElementById('chart-type');
    const dataViewFilter = document.getElementById('data-view');
    const fullscreenButtons = document.querySelectorAll('.chart-fullscreen');
    const downloadButtons = document.querySelectorAll('.chart-download');
    const trendButtons = document.querySelectorAll('.trend-toggle');
    const modal = document.getElementById('chart-modal');
    const modalClose = document.querySelector('.modal-close');
    const exportPdfBtn = document.getElementById('export-pdf');

    // Chart instances
    let distributionChart, trendChart, vsActualChart, breakdownChart;
    let modalChart = null;

    // Initialize dashboard
    initDashboard();

    // Event listeners
    if (periodButtons) {
        periodButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                periodButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                updateCharts(this.dataset.period);
            });
        });
    }

    if (chartTypeFilter) chartTypeFilter.addEventListener('change', filterCharts);
    if (dataViewFilter) dataViewFilter.addEventListener('change', updateDataView);

    if (fullscreenButtons) {
        fullscreenButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                openFullscreenChart(this.dataset.chart);
            });
        });
    }

    if (downloadButtons) {
        downloadButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                downloadChart(this.dataset.chart);
            });
        });
    }

    if (trendButtons) {
        trendButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                trendButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                updateTrendChart(this.dataset.trend);
            });
        });
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);

    if (exportPdfBtn) exportPdfBtn.addEventListener('click', exportDashboardToPdf);

    // Initialize dashboard
    function initDashboard() {
        fetchBudgetData()
            .then(data => {
                updateMetrics(data);
                initCharts(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showEmptyState();
            });
    }

    // Fetch budget data from API
    async function fetchBudgetData() {
        const response = await fetch('get_budget_data.php');
        if (!response.ok) throw new Error('Network response was not ok');
        return await response.json();
    }

    // Update metrics
    function updateMetrics(data) {
        try {
            document.getElementById('total-budget-metric').textContent = `$${data.totalBudget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('total-spent-metric').textContent = `$${data.totalSpent.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const savingsRate = data.totalBudget > 0
                ? ((data.totalBudget - data.totalSpent) / data.totalBudget * 100).toFixed(1)
                : 0;
            document.getElementById('savings-rate-metric').textContent = `${savingsRate}%`;

            const overspending = Math.max(0, data.totalSpent - data.totalBudget);
            document.getElementById('overspending-metric').textContent = `$${overspending.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            // Update trend indicators
            const budgetChangeElements = document.querySelectorAll('.metric-change');
            if (budgetChangeElements.length >= 4) {
                budgetChangeElements[0].querySelector('span').textContent = '5.2%';
                budgetChangeElements[1].querySelector('span').textContent = '3.8%';
                budgetChangeElements[2].querySelector('span').textContent = '1.4%';
                budgetChangeElements[3].querySelector('span').textContent = '2.1%';
            }
        } catch (e) {
            console.error('Error updating metrics:', e);
        }
    }

    // Initialize charts
    function initCharts(data) {
        // Distribution Chart
        initDistributionChart(data);

        // Trend Chart
        initTrendChart(data);

        // Budget vs Actual Chart
        initVsActualChart(data);

        // Category Breakdown Chart
        initBreakdownChart(data);
    }

    function initDistributionChart(data) {
        const ctx = document.getElementById('budgetDistributionChart');
        if (!ctx) return;

        distributionChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.categories.map(c => c.name),
                datasets: [{
                    data: data.categories.map(c => c.budget),
                    backgroundColor: generateCategoryColors(data.categories.length),
                    borderColor: 'var(--dark-surface-light)',
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = data.totalBudget > 0 ? (value / data.totalBudget * 100).toFixed(1) : '0.0';
                                return `${label}: $${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${percentage}%)`;
                            }
                        }
                    },
                    datalabels: {
                        color: 'var(--text-primary)',
                        font: {
                            weight: 'bold',
                            family: 'Inter'
                        },
                        formatter: (value, context) => {
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            return total > 0 ? `${((value / total) * 100).toFixed(1)}%` : '0%';
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Update legend
        updateDistributionLegend(data);
    }

    function updateDistributionLegend(data) {
        const legendContainer = document.getElementById('distribution-legend');
        if (!legendContainer) return;

        legendContainer.innerHTML = '';

        data.categories.forEach((category, index) => {
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';

            const colorBox = document.createElement('div');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = generateCategoryColors(data.categories.length)[index];

            const label = document.createElement('span');
            label.textContent = `${category.name}: $${category.budget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            legendItem.appendChild(colorBox);
            legendItem.appendChild(label);
            legendContainer.appendChild(legendItem);
        });
    }

    function initTrendChart(data) {
        const ctx = document.getElementById('spendingTrendChart');
        if (!ctx) return;

        // Prepare data for 12 months
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const currentMonth = new Date().getMonth();
        const monthLabels = months.slice(0, currentMonth + 1);

        // Example data (replace with actual data)
        const demoData = {
            budget: Array.from({ length: currentMonth + 1 }, (_, i) => 2000 + Math.random() * 500),
            spent: Array.from({ length: currentMonth + 1 }, (_, i) => 1500 + Math.random() * 800)
        };

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Budget',
                        data: demoData.budget,
                        borderColor: 'rgba(212, 175, 55, 0.8)',
                        backgroundColor: 'transparent',
                        borderWidth: 3,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(212, 175, 55, 1)'
                    },
                    {
                        label: 'Actual Spending',
                        data: demoData.spent,
                        borderColor: 'rgba(76, 201, 240, 0.8)',
                        backgroundColor: 'transparent',
                        borderWidth: 3,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(76, 201, 240, 1)'
                    },
                    {
                        label: 'Forecast',
                        data: [...demoData.spent, ...forecastSpending(demoData.spent)],
                        borderColor: 'rgba(244, 67, 54, 0.6)',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.3,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: 'var(--text-primary)',
                            font: {
                                family: 'Inter'
                            },
                            boxWidth: 12,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.dataset.label}: $${context.raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'var(--text-secondary)',
                            callback: function (value) {
                                return '$' + value.toLocaleString('en-US');
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    }

    function forecastSpending(data) {
        if (!data || data.length === 0) return [0, 0, 0];

        const lastValue = data[data.length - 1];
        const growthRate = 0.05; // 5% monthly growth

        return [
            lastValue * (1 + growthRate),
            lastValue * Math.pow(1 + growthRate, 2),
            lastValue * Math.pow(1 + growthRate, 3)
        ];
    }

    function initVsActualChart(data) {
        const ctx = document.getElementById('budgetVsActualChart');
        if (!ctx) return;

        vsActualChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.categories.map(c => c.name),
                datasets: [
                    {
                        label: 'Budget',
                        data: data.categories.map(c => c.budget),
                        backgroundColor: 'rgba(212, 175, 55, 0.6)',
                        borderColor: 'rgba(212, 175, 55, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Actual',
                        data: data.categories.map(c => c.spent),
                        backgroundColor: 'rgba(76, 201, 240, 0.6)',
                        borderColor: 'rgba(76, 201, 240, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: 'var(--text-primary)',
                            font: {
                                family: 'Inter'
                            },
                            boxWidth: 12,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.dataset.label}: $${context.raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'var(--text-secondary)',
                            callback: function (value) {
                                return '$' + value.toLocaleString('en-US');
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });

        // Update variance summary
        updateVarianceSummary(data);
    }

    function updateVarianceSummary(data) {
        const summaryContainer = document.getElementById('variance-summary');
        if (!summaryContainer) return;

        summaryContainer.innerHTML = '';

        // Top 3 positive variances
        const positiveVariances = [...data.categories]
            .filter(c => c.budget - c.spent > 0)
            .sort((a, b) => (b.budget - b.spent) - (a.budget - a.spent))
            .slice(0, 3);

        // Top 3 negative variances
        const negativeVariances = [...data.categories]
            .filter(c => c.budget - c.spent < 0)
            .sort((a, b) => (a.budget - a.spent) - (b.budget - b.spent))
            .slice(0, 3);

        if (positiveVariances.length > 0) {
            const heading = document.createElement('h4');
            heading.textContent = 'Top Savings';
            heading.style.color = 'var(--success)';
            summaryContainer.appendChild(heading);

            positiveVariances.forEach(category => {
                const varianceItem = document.createElement('div');
                varianceItem.className = 'variance-item positive';
                varianceItem.innerHTML = `
                    <span>${category.name}</span>
                    <span>$${(category.budget - category.spent).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                `;
                summaryContainer.appendChild(varianceItem);
            });
        }

        if (negativeVariances.length > 0) {
            const heading = document.createElement('h4');
            heading.textContent = 'Top Overspending';
            heading.style.color = 'var(--danger)';
            summaryContainer.appendChild(heading);

            negativeVariances.forEach(category => {
                const varianceItem = document.createElement('div');
                varianceItem.className = 'variance-item negative';
                varianceItem.innerHTML = `
                    <span>${category.name}</span>
                    <span>$${Math.abs(category.budget - category.spent).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                `;
                summaryContainer.appendChild(varianceItem);
            });
        }
    }

    function initBreakdownChart(data) {
        const ctx = document.getElementById('categoryBreakdownChart');
        if (!ctx) return;

        breakdownChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.categories.map(c => c.name),
                datasets: [{
                    label: 'Spent',
                    data: data.categories.map(c => c.spent),
                    backgroundColor: data.categories.map(c =>
                        c.spent > c.budget ? 'rgba(244, 67, 54, 0.6)' : 'rgba(76, 175, 80, 0.6)'
                    ),
                    borderColor: data.categories.map(c =>
                        c.spent > c.budget ? 'rgba(244, 67, 54, 1)' : 'rgba(76, 175, 80, 1)'
                    ),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.label}: $${context.raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'var(--text-secondary)',
                            callback: function (value) {
                                return '$' + value.toLocaleString('en-US');
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });

        // Update performance table
        updatePerformanceTable(data);
    }

    function updatePerformanceTable(data) {
        const tbody = document.querySelector('#category-performance-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        data.categories.forEach(category => {
            const row = document.createElement('tr');
            const variance = category.budget - category.spent;
            const varianceClass = variance >= 0 ? 'positive' : 'negative';
            const percent = (category.spent / category.budget * 100).toFixed(1);

            row.innerHTML = `
                <td>${category.name}</td>
                <td>$${category.budget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td>$${category.spent.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td class="${varianceClass}">$${Math.abs(variance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td>${percent}%</td>
            `;
            tbody.appendChild(row);
        });
    }

    function updateCharts(period) {
        // In a real app, this would filter data based on the selected period
        console.log(`Updating charts for period: ${period}`);
        // For now, we'll just add a slight animation to show the update
        document.querySelectorAll('.chart-card').forEach(card => {
            card.style.animation = 'none';
            void card.offsetWidth; // Trigger reflow
            card.style.animation = 'fadeInScale 0.5s ease-out forwards';
        });
    }

    function filterCharts() {
        try {
            const filterValue = this.value;
            document.querySelectorAll('.chart-card').forEach(card => {
                if (filterValue === 'all' || card.dataset.chartType === filterValue) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        } catch (e) {
            console.error('Error filtering charts:', e);
        }
    }

    function updateDataView() {
        try {
            const view = this.value;

            if (view === 'percentages') {
                // Update charts to show percentages
                if (distributionChart) {
                    distributionChart.options.plugins.datalabels.formatter = (value, context) => {
                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        return total > 0 ? `${((value / total) * 100).toFixed(1)}%` : '0%';
                    };
                    distributionChart.update();
                }

                // Similar updates for other charts would go here
            } else {
                // Revert to showing amounts
                if (distributionChart) {
                    distributionChart.options.plugins.datalabels.formatter = (value, context) => {
                        return `$${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    };
                    distributionChart.update();
                }
            }
        } catch (e) {
            console.error('Error updating data view:', e);
        }
    }

    function updateTrendChart(range) {
        try {
            // In a real app, this would adjust the time range of the trend chart
            console.log(`Updating trend chart for range: ${range}`);

            // For demo, just update the chart title
            const trendTitle = document.querySelector('[data-chart-type="trend"] h2');
            if (trendTitle) {
                trendTitle.innerHTML = `<i class="fas fa-chart-line"></i> Spending Trend (${range.replace('months', ' Months')})`;
            }
        } catch (e) {
            console.error('Error updating trend chart:', e);
        }
    }

    function openFullscreenChart(chartType) {
        try {
            if (!modal) return;

            // Set modal title
            const modalTitle = document.getElementById('modal-chart-title');
            const chartCard = document.querySelector(`[data-chart-type="${chartType}"]`);

            if (!modalTitle || !chartCard) return;

            const chartTitle = chartCard.querySelector('h2').textContent;
            modalTitle.textContent = chartTitle;

            // Get the original chart
            let originalChart;
            switch (chartType) {
                case 'distribution':
                    originalChart = distributionChart;
                    break;
                case 'trend':
                    originalChart = trendChart;
                    break;
                case 'comparison':
                    originalChart = vsActualChart;
                    break;
                case 'breakdown':
                    originalChart = breakdownChart;
                    break;
                default:
                    return;
            }

            if (!originalChart) return;

            // Create a clone of the chart in the modal
            const modalCtx = document.getElementById('modal-chart');
            if (!modalCtx) return;

            // Destroy previous modal chart if exists
            if (modalChart) {
                modalChart.destroy();
            }

            // Get the original canvas dimensions
            const originalCanvas = originalChart.canvas;
            const originalWidth = originalCanvas.width;
            const originalHeight = originalCanvas.height;

            // Set modal canvas to same dimensions as original
            modalCtx.width = originalWidth;
            modalCtx.height = originalHeight;

            // Create new chart with exact same configuration
            modalChart = new Chart(modalCtx.getContext('2d'), {
                type: originalChart.config.type,
                data: JSON.parse(JSON.stringify(originalChart.data)),
                options: {
                    ...JSON.parse(JSON.stringify(originalChart.options)),
                    // Ensure responsive is false to maintain exact size
                    responsive: false,
                    // Maintain original aspect ratio
                    maintainAspectRatio: false
                },
                plugins: originalChart.config.plugins ? [...originalChart.config.plugins] : []
            });

            // Show modal
            modal.classList.add('active');
        } catch (e) {
            console.error('Error opening fullscreen chart:', e);
        }
    }

    function closeModal() {
        try {
            if (!modal) return;
            modal.classList.remove('active');
        } catch (e) {
            console.error('Error closing modal:', e);
        }
    }

    function downloadChart(chartType) {
        try {
            let chartCanvas;
            switch (chartType) {
                case 'distribution':
                    chartCanvas = document.getElementById('budgetDistributionChart');
                    break;
                case 'trend':
                    chartCanvas = document.getElementById('spendingTrendChart');
                    break;
                case 'comparison':
                    chartCanvas = document.getElementById('budgetVsActualChart');
                    break;
                case 'breakdown':
                    chartCanvas = document.getElementById('categoryBreakdownChart');
                    break;
                default:
                    return;
            }

            if (!chartCanvas) return;

            const link = document.createElement('a');
            link.download = `budget-${chartType}-chart-${new Date().toISOString().slice(0, 10)}.png`;
            link.href = chartCanvas.toDataURL('image/png');
            link.click();
        } catch (e) {
            console.error('Error downloading chart:', e);
        }
    }

    function exportDashboardToPdf() {
        // First ensure all charts are fully rendered
        setTimeout(() => {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                // ===== DOCUMENT SETUP =====
                doc.setProperties({
                    title: 'Expencio Financial Analysis Report',
                    subject: 'Complete Budget Analysis',
                    author: 'Expencio Analytics'
                });

                // ===== COVER PAGE =====
                createCoverPage(doc);

                // ===== BUDGET ALLOCATION =====
                addChartPage(doc, 
                    'budgetDistributionChart',
                    'Budget Allocation',
                    [
                        'Shows how your budget is distributed across categories',
                        '',
                        'Key Insights:',
                        '• Identifies largest budget categories',
                        '• Reveals spending priorities',
                        '',
                        'Recommendations:',
                        '1. Review categories >25% of total',
                        '2. Ensure essentials are properly funded'
                    ],
                    { x: 40, y: 40, width: 130, height: 130 }
                );

                // ===== SPENDING TREND =====
                addChartPage(doc,
                    'spendingTrendChart',
                    'Spending Trend with Forecast',
                    [
                        'Tracks actual spending vs budget over time',
                        '',
                        'Key Insights:',
                        '• Shows monthly patterns',
                        '• Reveals budget adherence',
                        '',
                        'Recommendations:',
                        '1. Investigate large variances',
                        '2. Adjust future budgets'
                    ],
                    { x: 20, y: 40, width: 170, height: 100 }
                );

                // ===== BUDGET VS ACTUAL =====
                addChartPage(doc,
                    'budgetVsActualChart',
                    'Budget vs Actual Variance',
                    [
                        'Compares budgeted vs actual spending',
                        '',
                        'Key Insights:',
                        '• Identifies problem categories',
                        '• Shows spending patterns',
                        '',
                        'Recommendations:',
                        '1. Address overspending',
                        '2. Reallocate unused funds'
                    ],
                    { x: 20, y: 40, width: 170, height: 100 }
                );

                // ===== CATEGORY PERFORMANCE =====
                addCategoryPerformancePage(doc);

                // ===== FINALIZE DOCUMENT =====
                addPageNumbers(doc);
                doc.save(`Expencio_Report_${new Date().toISOString().slice(0,10)}.pdf`);

            } catch (e) {
                console.error('PDF generation failed:', e);
                alert('Error generating PDF. Please try again.');
            }
        }, 500); // Small delay to ensure charts are ready
    }

    function createCoverPage(doc) {
        doc.setFillColor(248, 248, 248);
        doc.rect(0, 0, 210, 297, 'F');
        
        doc.setFontSize(24);
        doc.setTextColor(212, 175, 55);
        doc.setFont('helvetica', 'bold');
        doc.text('Expencio', 105, 50, { align: 'center' });
        
        doc.setFontSize(28);
        doc.setTextColor(51, 51, 51);
        doc.text('Financial Report', 105, 70, { align: 'center' });
        
        doc.setFontSize(16);
        doc.text('Comprehensive Budget Analysis', 105, 85, { align: 'center' });
        
        doc.setDrawColor(212, 175, 55);
        doc.setLineWidth(0.8);
        doc.line(50, 95, 160, 95);
        
        doc.setFontSize(14);
        doc.setTextColor(102, 102, 102);
        doc.text(`Prepared for: ${document.querySelector('.username').textContent}`, 105, 115, { align: 'center' });
        doc.text(`Period: ${document.querySelector('.time-period-selector .btn.active').textContent.trim()}`, 105, 125, { align: 'center' });
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 105, 135, { align: 'center' });
    }

    function addChartPage(doc, chartId, title, description, position) {
        doc.addPage();
        
        // Header
        doc.setFontSize(20);
        doc.setFont('helvetica', 'bold');
        doc.text(title, 20, 20);
        doc.setDrawColor(212, 175, 55);
        doc.line(20, 23, title.length * 3, 23);
        
        // Get chart canvas
        const canvas = document.getElementById(chartId);
        if (!canvas) {
            console.error(`Chart not found: ${chartId}`);
            return;
        }
        
        // Capture chart image
        const image = canvas.toDataURL('image/jpeg', 1.0);
        doc.addImage(image, 'JPEG', position.x, position.y, position.width, position.height);
        
        // Description
        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        doc.text(description, 20, position.y + position.height + 15, { maxWidth: 170 });
    }

    function addCategoryPerformancePage(doc) {
        doc.addPage();
        
        // Header
        doc.setFontSize(20);
        doc.setFont('helvetica', 'bold');
        doc.text('Category Performance', 20, 20);
        doc.setDrawColor(212, 175, 55);
        doc.line(20, 23, 70, 23);
        
        // Get chart canvas
        const canvas = document.getElementById('categoryBreakdownChart');
        if (canvas) {
            const image = canvas.toDataURL('image/jpeg', 1.0);
            doc.addImage(image, 'JPEG', 20, 30, 170, 100);
        }
        
        // Description
        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        doc.text([
            'Detailed view of budget vs actual by category',
            '',
            'Key Insights:',
            '• Shows complete category performance',
            '• Color-coded variances',
            '',
            'Recommendations:',
            '1. Focus on problem categories',
            '2. Celebrate on-target categories'
        ], 20, 140, { maxWidth: 170 });
        
        // Data table
        doc.setFontSize(10);
        doc.text('Detailed Data:', 20, 180);
        
        const headers = ['Category', 'Budget', 'Spent', 'Variance', '% of Total'];
        const rows = [];
        
        document.querySelectorAll('#category-performance-table tbody tr').forEach(row => {
            rows.push(Array.from(row.children).map(cell => {
                return cell.textContent.replace(/(positive|negative)/g, '').trim();
            }));
        });
        
        doc.autoTable({
            startY: 185,
            head: [headers],
            body: rows,
            theme: 'grid',
            headStyles: {
                fillColor: [212, 175, 55],
                textColor: 0,
                fontStyle: 'bold'
            },
            columnStyles: {
                0: { fontStyle: 'bold' },
                4: { halign: 'right' }
            },
            styles: { fontSize: 8 }
        });
    }

    function addPageNumbers(doc) {
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(10);
            doc.setTextColor(150, 150, 150);
            doc.text(`Page ${i} of ${pageCount}`, 105, 287, { align: 'center' });
        }
    }

    function showEmptyState() {
        try {
            const analyticsGrid = document.querySelector('.analytics-grid');
            if (analyticsGrid) {
                analyticsGrid.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-chart-pie"></i>
                        <h3>No Budget Data Available</h3>
                        <p>Add budget items in the Dashboard to see analytics</p>
                        <a href="basic.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-arrow-left"></i> Go to Dashboard
                        </a>
                    </div>
                `;
            }
        } catch (e) {
            console.error('Error showing empty state:', e);
        }
    }

    function generateCategoryColors(count) {
        const baseColors = [
            'rgba(212, 175, 55, 0.8)',  // Primary gold
            'rgba(76, 201, 240, 0.8)',   // Success blue
            'rgba(156, 39, 176, 0.8)',   // Purple
            'rgba(244, 67, 54, 0.8)',    // Danger red
            'rgba(33, 150, 243, 0.8)',   // Info blue
            'rgba(63, 81, 181, 0.8)',    // Indigo
            'rgba(76, 175, 80, 0.8)',    // Green
            'rgba(255, 152, 0, 0.8)'     // Orange
        ];

        // If we need more colors than we have base colors, generate variations
        const colors = [];
        for (let i = 0; i < count; i++) {
            if (i < baseColors.length) {
                colors.push(baseColors[i]);
            } else {
                // Generate a random color with good contrast
                const hue = Math.floor(Math.random() * 360);
                colors.push(`hsla(${hue}, 70%, 60%, 0.8)`);
            }
        }   

        return colors;
    }

    // Close modal when clicking outside
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
});