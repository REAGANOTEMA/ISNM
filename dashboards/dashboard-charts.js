/**
 * ISNM Dashboard Analytics Engine
 * Chart.js integration with AI-powered predictions and insights
 */
(function() {
    'use strict';

    // Global handler for unhandled promise rejections — already registered in dashboard_head.php

    const AI_ENGINE = {
        version: '1.0.0',
        modelId: 'isnm-predictor-v1',
        lastTraining: null,
        predictions: {},
        insights: [],

        /**
         * Generate trend prediction using moving average + linear regression
         */
        predict: function(data, horizon) {
            if (!data || data.length < 3) return [];
            horizon = horizon || 3;

            var n = data.length;
            var sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
            for (var i = 0; i < n; i++) {
                sumX += i;
                sumY += data[i];
                sumXY += i * data[i];
                sumX2 += i * i;
            }
            var denom = n * sumX2 - sumX * sumX;
            var slope = denom !== 0 ? (n * sumXY - sumX * sumY) / denom : 0;
            var intercept = denom !== 0 ? (sumY - slope * sumX) / n : (sumY / n);

            var predictions = [];
            for (var j = 1; j <= horizon; j++) {
                var pred = Math.max(0, intercept + slope * (n - 1 + j));
                predictions.push(Math.round(pred * 100) / 100);
            }
            return predictions;
        },

        /**
         * Generate natural language insight from data
         */
        generateInsight: function(label, current, previous, unit) {
            unit = unit || '';
            if (previous === 0) return null;
            var change = ((current - previous) / previous) * 100;
            var direction = change >= 0 ? 'increased' : 'decreased';
            var arrow = change >= 5 ? '\u2191' : change <= -5 ? '\u2193' : '\u2192';
            var severity = Math.abs(change) > 20 ? 'significantly' : Math.abs(change) > 10 ? 'moderately' : 'slightly';
            return {
                label: label,
                current: current,
                previous: previous,
                change: Math.round(change * 10) / 10,
                direction: direction,
                text: label + ' ' + direction + ' ' + severity + ' by ' + Math.abs(Math.round(change)) + '% ' + arrow,
                severity: severity,
                trend: change >= 0 ? 'up' : 'down'
            };
        },

        /**
         * Analyze payment patterns and detect anomalies
         */
        analyzePaymentTrend: function(payments) {
            if (!payments || payments.length < 4) return { trend: 'insufficient_data', confidence: 0 };
            var values = payments.map(function(p) { return p.amount || 0; });
            var mean = values.reduce(function(a, b) { return a + b; }, 0) / values.length;
            var std = Math.sqrt(values.reduce(function(sq, v) { return sq + Math.pow(v - mean, 2); }, 0) / values.length);
            var predictions = this.predict(values, 2);
            var lastVal = values[values.length - 1];
            var isAnomaly = Math.abs(lastVal - mean) > 2 * std;

            return {
                trend: predictions[0] > lastVal ? 'increasing' : predictions[0] < lastVal ? 'decreasing' : 'stable',
                confidence: Math.min(85, Math.round((1 - std / (mean || 1)) * 100)),
                nextPrediction: predictions[0] || 0,
                anomaly: isAnomaly,
                anomalyReason: isAnomaly ? 'Last value deviates significantly from pattern' : null,
                seasonality: this.detectSeasonality(payments)
            };
        },

        /**
         * Simple seasonality detection
         */
        detectSeasonality: function(data) {
            if (data.length < 8) return null;
            var half = Math.floor(data.length / 2);
            var firstHalf = data.slice(0, half).reduce(function(s, p) { return s + (p.amount || 0); }, 0) / half;
            var secondHalf = data.slice(half).reduce(function(s, p) { return s + (p.amount || 0); }, 0) / half;
            var ratio = firstHalf > 0 ? secondHalf / firstHalf : 1;
            if (ratio > 1.3) return 'increasing seasonality detected';
            if (ratio < 0.7) return 'decreasing seasonality detected';
            return 'stable seasonality';
        },

        /**
         * Generate performance score (0-100)
         */
        calculatePerformanceScore: function(stats) {
            var score = 50;
            if (stats.attendanceRate > 90) score += 15;
            else if (stats.attendanceRate > 75) score += 8;
            else score -= 10;
            if (stats.passRate > 85) score += 15;
            else if (stats.passRate > 70) score += 8;
            else score -= 10;
            if (stats.collectionRate > 80) score += 10;
            else if (stats.collectionRate > 60) score += 5;
            else score -= 10;
            if (stats.staffMorale > 70) score += 10;
            else if (stats.staffMorale > 50) score += 5;
            else score -= 5;
            return Math.max(0, Math.min(100, score));
        }
    };

    window.ISNMAI = AI_ENGINE;

    /**
     * Initialize all charts on the page
     */
    window.initDashboardCharts = function(config) {
        config = config || {};
        var charts = {};

        function sanitizeData(d) {
            if (!Array.isArray(d)) return [];
            return d.map(function(v){ return (typeof v==='number' && isFinite(v)) ? v : 0; });
        }

        function createChart(canvasId, type, data, opts) {
            var canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            var ctx = canvas.getContext('2d');
            // Sanitize all numeric datasets
            if (data && data.datasets) {
                data.datasets.forEach(function(ds) {
                    if (ds.data) ds.data = sanitizeData(ds.data);
                });
            }
            var defaultOpts = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            };
            var merged = deepMerge(defaultOpts, opts || {});
            try {
                var chart = new Chart(ctx, { type: type, data: data, options: merged });
                charts[canvasId] = chart;
                return chart;
            } catch (e) {
                console.warn('Chart init error for ' + canvasId + ':', e);
                return null;
            }
        }

        function deepMerge(a, b) {
            var result = {};
            for (var k in a) result[k] = a[k];
            for (var k in b) {
                if (b[k] && typeof b[k] === 'object' && !Array.isArray(b[k])) {
                    result[k] = deepMerge(result[k] || {}, b[k]);
                } else {
                    result[k] = b[k];
                }
            }
            return result;
        }

        /**
         * Payment Revenue Chart (line + bar combo)
         */
        if (config.paymentData) {
            var pd = config.paymentData;
            var labels = pd.labels || [];
            var revenue = pd.revenue || [];
            var expenses = pd.expenses || [];

            if (document.getElementById('chartRevenue')) {
                createChart('chartRevenue', 'line', {
                    labels: labels,
                    datasets: [
                        { label: 'Revenue', data: revenue, borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.1)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Expenses', data: expenses, borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.05)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4, pointHoverRadius: 6, borderDash: [6, 3] }
                    ]
                }, {
                    scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return 'UGX ' + (v/1000).toFixed(0) + 'k'; } } } },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': UGX ' + Number(ctx.raw).toLocaleString(); } } }
                    }
                });
            }

            // Donut - payment methods distribution
            if (document.getElementById('chartPaymentMethods') && pd.methods) {
                var methodColors = ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899'];
                createChart('chartPaymentMethods', 'doughnut', {
                    labels: pd.methods.labels,
                    datasets: [{ data: pd.methods.values, backgroundColor: methodColors.slice(0, pd.methods.labels.length), borderWidth: 2, borderColor: '#fff' }]
                }, {
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 10 } } }
                    }
                });
            }

            // Bar - monthly comparison
            if (document.getElementById('chartMonthlyComparison') && pd.monthly) {
                createChart('chartMonthlyComparison', 'bar', {
                    labels: pd.monthly.labels,
                    datasets: [
                        { label: 'Collection', data: pd.monthly.collection, backgroundColor: 'rgba(5,150,105,0.7)', borderColor: '#059669', borderWidth: 2, borderRadius: 6 },
                        { label: 'Target', data: pd.monthly.targets, backgroundColor: 'rgba(59,130,246,0.2)', borderColor: '#3b82f6', borderWidth: 2, borderRadius: 6, borderDash: [4, 2] }
                    ]
                }, {
                    scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return 'UGX ' + (v/1000).toFixed(0) + 'k'; } } } },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': UGX ' + Number(ctx.raw).toLocaleString(); } } }
                    }
                });
            }
        }

        /**
         * Student Performance Chart
         */
        if (config.studentData) {
            var sd = config.studentData;

            if (document.getElementById('chartStudentPerformance')) {
                createChart('chartStudentPerformance', 'radar', {
                    labels: sd.metrics || ['Attendance', 'Academics', 'Discipline', 'Participation', 'Health'],
                    datasets: [{
                        label: sd.label || 'Performance',
                        data: sd.values || [85, 78, 92, 70, 88],
                        backgroundColor: 'rgba(5,150,105,0.2)',
                        borderColor: '#059669',
                        borderWidth: 2,
                        pointBackgroundColor: '#059669',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    }]
                }, {
                    scales: { r: { beginAtZero: true, max: 100, ticks: { stepSize: 20 } } },
                    plugins: { legend: { position: 'bottom' } }
                });
            }
        }

        /**
         * Staff Analytics Chart
         */
        if (config.staffData) {
            var std = config.staffData;

            if (document.getElementById('chartStaffAttendance')) {
                createChart('chartStaffAttendance', 'doughnut', {
                    labels: ['Present', 'Late', 'Absent', 'On Leave'],
                    datasets: [{
                        data: [std.present || 0, std.late || 0, std.absent || 0, std.on_leave || std.onLeave || 0],
                        backgroundColor: ['#059669', '#f59e0b', '#dc2626', '#3b82f6'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                }, {
                    cutout: '60%',
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 10 } } }
                    }
                });
            }

            if (document.getElementById('chartDepartmentDistribution')) {
                createChart('chartDepartmentDistribution', 'bar', {
                    labels: std.departments || [],
                    datasets: [{
                        label: 'Staff Count',
                        data: std.counts || [],
                        backgroundColor: ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6'],
                        borderRadius: 6
                    }]
                }, {
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                    plugins: { legend: { display: false } }
                });
            }
        }

        /**
         * Inventory / Stock Chart
         */
        if (config.inventoryData) {
            var inv = config.inventoryData;

            if (document.getElementById('chartInventoryStatus')) {
                createChart('chartInventoryStatus', 'bar', {
                    labels: inv.labels || [],
                    datasets: [{
                        label: 'Current Stock',
                        data: inv.current || [],
                        backgroundColor: inv.current.map(function(v, i) { return v < (inv.min || [])[i] ? 'rgba(220,38,38,0.7)' : 'rgba(5,150,105,0.7)'; }),
                        borderRadius: 6
                    }]
                }, {
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                });
            }
        }

        /**
         * Resize handler
         */
        window.addEventListener('resize', function() {
            for (var id in charts) {
                if (charts[id] && typeof charts[id].resize === 'function') {
                    charts[id].resize();
                }
            }
        });

        return charts;
    };

    /**
     * Render AI insights panel
     */
    window.renderAIInsights = function(containerId, insights) {
        var container = document.getElementById(containerId);
        if (!container) return;

        if (!insights || insights.length === 0) {
            container.innerHTML = '<div class="text-muted small text-center py-3"><i class="fas fa-robot me-1"></i>No insights available yet. Data is being analyzed...</div>';
            return;
        }

        var html = '<div class="ai-insights-list">';
        insights.forEach(function(insight) {
            var iconClass = insight.trend === 'up' ? 'fa-arrow-up text-success' : insight.trend === 'down' ? 'fa-arrow-down text-danger' : 'fa-minus text-muted';
            var bgClass = insight.trend === 'up' ? 'bg-success-subtle' : insight.trend === 'down' ? 'bg-danger-subtle' : 'bg-light';
            html += '<div class="ai-insight-item ' + bgClass + '">';
            html += '<div class="d-flex align-items-start gap-2">';
            html += '<i class="fas ' + iconClass + ' mt-1"></i>';
            html += '<div class="flex-grow-1">';
            html += '<div class="fw-semibold small">' + (insight.label || 'Insight') + '</div>';
            html += '<div class="text-muted" style="font-size:11px">' + (insight.text || '') + '</div>';
            if (insight.change !== undefined) {
                html += '<div class="mt-1"><span class="badge bg-' + (insight.change >= 0 ? 'success' : 'danger') + '" style="font-size:10px">' + (insight.change >= 0 ? '+' : '') + insight.change + '%</span></div>';
            }
            html += '</div></div></div>';
        });
        html += '</div>';
        container.innerHTML = html;
    };

    /**
     * Render AI prediction card
     */
    window.renderAIPrediction = function(containerId, predictions, label, unit) {
        var container = document.getElementById(containerId);
        if (!container || !predictions || predictions.length === 0) return;

        unit = unit || '';
        var trend = predictions[0] > predictions[predictions.length - 1] ? 'up' : 'down';
        var icon = trend === 'up' ? 'fa-chart-line text-success' : 'fa-chart-line text-danger';
        var direction = trend === 'up' ? 'Increase predicted' : 'Decrease predicted';

        var html = '<div class="ai-prediction-card">';
        html += '<div class="d-flex align-items-center gap-2 mb-2">';
        html += '<i class="fas ' + icon + '"></i>';
        html += '<span class="fw-semibold small">AI Prediction</span>';
        html += '<span class="badge bg-primary ms-auto" style="font-size:9px">ML v1.0</span>';
        html += '</div>';
        html += '<div class="prediction-values d-flex gap-2 justify-content-center my-2">';
        predictions.forEach(function(p, i) {
            html += '<div class="text-center px-2">';
            html += '<div class="fw-bold" style="font-size:18px;color:var(--theme-primary,#1a237e)">' + Number(p).toLocaleString() + '</div>';
            html += '<div class="text-muted" style="font-size:10px">' + (i === 0 ? 'Next' : '+' + (i + 1)) + '</div>';
            html += '</div>';
            if (i < predictions.length - 1) html += '<div class="d-flex align-items-center text-muted"><i class="fas fa-arrow-right" style="font-size:10px"></i></div>';
        });
        html += '</div>';
        html += '<div class="text-center text-muted" style="font-size:10px">' + direction + ' &middot; ' + label + '</div>';
        html += '</div>';
        container.innerHTML = html;
    };

    /**
     * Performance gauge (circular)
     */
    window.renderPerformanceGauge = function(containerId, score, label) {
        var container = document.getElementById(containerId);
        if (!container) return;

        var color = score >= 80 ? '#059669' : score >= 60 ? '#f59e0b' : '#dc2626';
        var grade = score >= 85 ? 'Excellent' : score >= 70 ? 'Good' : score >= 55 ? 'Average' : 'Needs Improvement';

        var circumference = 2 * Math.PI * 54;
        var offset = circumference - (score / 100) * circumference;

        var html = '<div class="text-center">';
        html += '<svg width="140" height="140" viewBox="0 0 140 140">';
        html += '<circle cx="70" cy="70" r="54" fill="none" stroke="#e5e7eb" stroke-width="12"/>';
        html += '<circle cx="70" cy="70" r="54" fill="none" stroke="' + color + '" stroke-width="12" stroke-dasharray="' + circumference + '" stroke-dashoffset="' + offset + '" stroke-linecap="round" transform="rotate(-90 70 70)" style="transition: stroke-dashoffset 1.5s ease-in-out"/>';
        html += '<text x="70" y="65" text-anchor="middle" font-size="28" font-weight="800" fill="' + color + '">' + score + '</text>';
        html += '<text x="70" y="85" text-anchor="middle" font-size="10" fill="#64748b">/ 100</text>';
        html += '</svg>';
        html += '<div class="fw-bold mt-1" style="color:' + color + ';font-size:13px">' + grade + '</div>';
        if (label) html += '<div class="text-muted" style="font-size:11px">' + label + '</div>';
        html += '</div>';
        container.innerHTML = html;
    };

    // ── Auto-init from data attribute ──
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var bar = document.querySelector('.analytics-bar, .analytics-strip');
            if (!bar) return;
            var raw = bar.getAttribute('data-ax');
            if (!raw) return;
            var d;
            try { d = JSON.parse(raw); } catch(e) { return; }
            if (!d || !d.months) return;

            initDashboardCharts({
                paymentData: {
                    labels: d.months, revenue: d.rev || [], expenses: d.exp || [],
                    methods: { labels: d.methods && d.methods.l ? d.methods.l : [], values: d.methods && d.methods.v ? d.methods.v : [] },
                    monthly: { labels: d.months, collection: d.rev || [], targets: (d.rev || []).map(function(v){return v*1.15;}) }
                },
                staffData: d.attendance || {}
            });

            if (typeof ISNMAI === 'undefined' || !d.rev || !d.exp) return;
            var insights = [];
            var rev = d.rev || [], exp = d.exp || [];
            var pR = rev.length>=2?rev[rev.length-2]:0, cR = rev.length?rev[rev.length-1]:0;
            var pE = exp.length>=2?exp[exp.length-2]:0, cE = exp.length?exp[exp.length-1]:0;
            var rT = rev.reduce(function(a,b){return a+b;},0), eT = exp.reduce(function(a,b){return a+b;},0);
            var i1 = ISNMAI.generateInsight('Revenue', cR, pR, 'UGX'); if(i1) insights.push(i1);
            var i2 = ISNMAI.generateInsight('Expenses', cE, pE, 'UGX'); if(i2) insights.push(i2);
            if(rT>0){var m=((rT-eT)/rT)*100;insights.push({label:'Margin',text:m.toFixed(1)+'% '+(m>20?'Healthy':m>10?'Moderate':'Low'),trend:m>15?'up':'down',change:Math.round(m*10)/10});}
            var att = d.attendance || {};
            var tS = (att.present||0)+(att.late||0)+(att.absent||0)+(att.on_leave||att.onLeave||0);
            var aR = tS>0?Math.round((att.present||0)/tS*100):0;
            insights.push({label:'Attendance',text:aR+'% today ('+(att.present||0)+' present)',trend:aR>=80?'up':'down',change:aR});
            if (typeof renderAIInsights === 'function') renderAIInsights('aiInsightsPanel', insights);
            if (rev.length>0) { var pred = ISNMAI.predict(rev, 3); if (typeof renderAIPrediction === 'function') renderAIPrediction('aiPredictionPanel', pred, 'Revenue Forecast', 'UGX'); }
            var score = ISNMAI.calculatePerformanceScore({ attendanceRate: aR, passRate: 82, collectionRate: d.collRate||50, staffMorale: 70 });
            if (typeof renderPerformanceGauge === 'function') renderPerformanceGauge('performanceGauge', score, 'Health');
        } catch(e) {
            // Silently handle any chart init errors
        }
    });
})();
