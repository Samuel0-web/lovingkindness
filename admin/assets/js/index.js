// assets/js/index.js - Dashboard Interactive Features (Production Safe)
(function() {
    'use strict';

    // Get computed CSS variables for Chart.js
    const rootStyles = getComputedStyle(document.documentElement);
    const brandGreen =
    rootStyles.getPropertyValue('--brand-green').trim() || '#0f5b3e';
    const brandGold = rootStyles.getPropertyValue('--brand-gold').trim() || '#e8b12c';
    const gray200 = rootStyles.getPropertyValue('--gray-200').trim() || '#e5e7eb';
    const gray600 = rootStyles.getPropertyValue('--gray-600').trim() || '#6b7280';
    const gray800 = rootStyles.getPropertyValue('--gray-800').trim() || '#1f2937';
    const white = rootStyles.getPropertyValue('--white').trim() || '#ffffff';

    // ========== COUNTER ANIMATION (supports float for conversion rate) ==========
    function animateNumbers() {
        const statValues = document.querySelectorAll('.stat-value[data-target]');
        statValues.forEach(el => {
            const target = parseFloat(el.getAttribute('data-target'));
            if (isNaN(target)) return;
            
            const start = parseFloat(el.textContent.replace(/,/g, '')) || 0;
            const steps = 50;
            const increment = (target - start) / steps;
            const isFloat = target !== Math.floor(target);
            let current = start;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    el.textContent = isFloat ? current.toFixed(1) : 
                    Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    el.textContent = isFloat ? target.toFixed(1) : target.toLocaleString();
                }
            };
            updateCounter();
        });
        
        // Animate message stats
        const msgStats = document.querySelectorAll('.msg-card strong[data-target]');
        msgStats.forEach(el => {
            const target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target)) return;
            
            const start = parseFloat(el.textContent.replace(/,/g, '')) || 0;
            const steps = 50;
            const increment = (target - start) / steps;
            let current = start;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    el.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    el.textContent = target.toLocaleString();
                }
            };
            updateCounter();
        });
    }

    // ========== LIVE DATE ==========
    function updateDate() {
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateElement.textContent = now.toLocaleDateString('en-US', options);
        }
    }

    // ========== CHART.JS INITIALIZATION (with CSS variable fix) ==========
    let trendChart, distributionChart;

    function initCharts() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js failed to load');
            return;
        }

        if (trendChart) trendChart.destroy();
        if (distributionChart) distributionChart.destroy();

        const trendCtx = document.getElementById('trendChart')?.getContext('2d');
        const distCtx = document.getElementById('distributionChart')?.getContext('2d');
        const monthlyData = window.dashboardData?.monthlyEnrollments || [];
        const monthlyLabels = window.dashboardData?.monthlyLabels || [];
        const programData = window.dashboardData?.programDistribution || [];
        const programLabels = window.dashboardData?.programLabels || [];
        
        if (trendCtx && monthlyData.length > 0) {
            trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Enrollments',
                        data: monthlyData,
                        borderColor: brandGreen,
                        backgroundColor: '#0f5b3e0d',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: brandGreen,
                        pointBorderColor: white,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { 
                        legend: { labels: { color: gray600 } },
                        tooltip: { backgroundColor: white, titleColor: gray800, bodyColor: gray600 }
                    },
                    scales: {
                        y: {
                            ticks: {
                                precision: 0,
                                stepSize: 1
                            },
                            grid: {
                                color: gray200
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        } else if (trendCtx) {
            trendChart = new Chart(trendCtx, {
                type: 'line',
                data: { labels: [], datasets: [{ label: 'Enrollments', data: [] }] },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        }
        
        if (distCtx && programData.length > 0) {
            distributionChart = new Chart(distCtx, {
                type: 'doughnut',
                data: {
                    labels: programLabels.map(formatLabel),
                    datasets: [{
                        data: programData,
                        backgroundColor: [
                            brandGreen,
                            brandGold,
                            '#3b82f6',
                            '#8b5cf6',
                            '#ef4444',
                            '#14b8a6'
                        ],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '60%',
                    plugins: { 
                        legend: { position: 'bottom', labels: { color: gray600 } },
                        tooltip: {
                            displayColors: false,
                            callbacks: {
                                title: (context) => context[0].label,
                                label: (context) => `${context.raw} enrollments`
                            }
                        }
                    }
                }
            });
        } else if (distCtx) {
            distributionChart = new Chart(distCtx, {
                type: 'doughnut',
                data: { labels: ['No data'], datasets: [{ data: [1], backgroundColor: [gray200] }] },
                options: { responsive: true, cutout: '60%', plugins: { legend: { display: false } } }
            });
        }
    }

    function formatLabel(label) {
        return label
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }

    // ========== PROGRESS BARS (no inline styles) ==========
    function initProgressBars() {
        document.querySelectorAll('.progress[data-width]').forEach(bar => {
            bar.dataset.realWidth = bar.dataset.width;
            bar.style.width = '0%';
        });
    }

    // ========== REFRESH DASHBOARD ACTION ==========
    function initRefreshButton() {
        const refreshBtn = document.getElementById('refreshDashboardBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', async function() {
                const originalHtml = this.innerHTML;
                this.innerHTML =
                    '<i class="fas fa-spinner fa-pulse"></i> Refreshing...';

                this.disabled = true;

                try {
                    await loadDashboard();
                } catch(error) {
                    console.error(error);
                } finally {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }

            });
        }
    }

    // ========== INTERSECTION OBSERVER (Fade-in animations) ==========
    function initScrollAnimations() {
        const sections = document.querySelectorAll('.stats-grid, .analytics-row, .status-breakdown, .message-overview, .recent-section, .dual-layout, .quick-actions-grid');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        
        sections.forEach(section => {
            if (section) {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(section);
            }
        });
    }

    // ========== PROGRESS BAR ANIMATION (on view) ==========
    function animateProgressBarsOnView() {
        const progressBars = document.querySelectorAll('.progress');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.width = entry.target.dataset.realWidth + '%';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        progressBars.forEach(bar => observer.observe(bar));
    }

    async function loadDashboard() {
        try {
            const response = await fetch('api/dashboard');
            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}`
                );
            }
            const data = await response.json();

            updateStats(data.stats);
            updateProgressBars(data.statusPercentages);
            animateNumbers();
            updateCharts(data);
        } catch(error) {
            console.error('Dashboard refresh failed:', error);
        }

    }

    function updateStats(stats) {
        document.querySelectorAll('.stat-value[data-target]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (stats[key] !== undefined) {
                el.setAttribute('data-target', stats[key]);
                el.textContent = stats[key];
            }
        });

        const messageMap = {
            unreadMessages: '.msg-card.unread strong',
            repliedMessages: '.msg-card.replied strong'
        };

        Object.entries(messageMap).forEach(([key, selector]) => {
            const el = document.querySelector(selector);

            if (el && stats[key] !== undefined) {
                el.setAttribute('data-target', stats[key]);
                el.textContent = stats[key];
            }
        });
    }

    function updateProgressBars(percentages) {
        if (!percentages) return;

        const map = {
            pending: '.status-item.pending .progress',
            contacted: '.status-item.contacted .progress',
            consultation_booked: '.status-item.booked .progress',
            enrolled: '.status-item.enrolled .progress',
            rejected: '.status-item.rejected .progress'
        };

        Object.entries(map).forEach(([key, selector]) => {
            const bar = document.querySelector(selector);

            if (!bar || percentages[key] === undefined) {
                return;
            }

            bar.dataset.realWidth = percentages[key];

            requestAnimationFrame(() => {
                bar.style.width = percentages[key] + '%';
            });
        });
    }

    function updateCharts(data) {
        if (trendChart) {
            trendChart.data.labels = data.monthlyLabels || [];
            trendChart.data.datasets[0].data = data.monthlyEnrollments || [];
            trendChart.update();
        }
        if (distributionChart) {
            distributionChart.data.labels = (data.programLabels || []).map(formatLabel);
            distributionChart.data.datasets[0].data = data.programDistribution || [];
            distributionChart.update();
        }
    }

    // ========== RESIZE HANDLER FOR CHARTS ==========
    let resizeTimer;
    function handleResize() {
        if (trendChart) trendChart.resize();
        if (distributionChart) distributionChart.resize();
    }
    
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleResize, 250);
    });

    document.querySelectorAll('.feed-item[data-message-id]').forEach(item => {
        item.addEventListener('click', () => {
            const id = item.dataset.messageId;
            window.location.href = `inbox?message=${id}`;
        });
    });

    // ========== INITIALIZE ALL ==========
    document.addEventListener('DOMContentLoaded', () => {
        updateDate();
        animateNumbers();
        initProgressBars();
        initCharts();
        initRefreshButton();
        initScrollAnimations();
        animateProgressBarsOnView();
        
        setInterval(updateDate, 60000);
    });
})();