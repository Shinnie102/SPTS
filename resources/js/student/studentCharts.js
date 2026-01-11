/**
 * Student Dashboard Charts System
 * Vẽ biểu đồ GPA và Tỷ lệ chuyên cần theo môn
 * Sử dụng Chart.js
 */

class StudentCharts {
    constructor(config = {}) {
        this.config = {
            gpaChartId: config.gpaChartId || 'gpaChart',
            attendanceChartId: config.attendanceChartId || 'attendanceChart',
            gpaApiEndpoint: config.gpaApiEndpoint || '/api/student/gpa-chart',
            attendanceApiEndpoint: config.attendanceApiEndpoint || '/api/student/attendance-chart',
            ...config
        };

        this.gpaChart = null;
        this.attendanceChart = null;
        this.currentSemester = 'HK2-2025-2026'; // Default semester
    }

    /**
     * Khởi tạo tất cả biểu đồ
     */
    async init() {
        try {
            await this.initGPAChart();
            await this.initAttendanceChart();
            this.attachEventListeners();
        } catch (error) {
            console.error('Lỗi khi khởi tạo biểu đồ:', error);
        }
    }

    /**
     * Khởi tạo biểu đồ GPA
     */
    async initGPAChart() {
        const canvas = document.getElementById(this.config.gpaChartId);
        if (!canvas) {
            console.error(`Canvas #${this.config.gpaChartId} không tồn tại`);
            return;
        }

        // Fetch data
        const data = await this.fetchGPAData();

        // Destroy old chart if exists
        if (this.gpaChart) {
            this.gpaChart.destroy();
        }

        // Create chart
        const ctx = canvas.getContext('2d');
        this.gpaChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'GPA',
                    data: data.values,
                    backgroundColor: '#2563eb',
                    borderRadius: 4,
                    barThickness: 60,
                    maxBarThickness: 80,
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
                        backgroundColor: '#1f2937',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'GPA: ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                family: 'Roboto, sans-serif'
                            },
                            color: '#6b7280',
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                // Wrap long labels
                                if (label.length > 15) {
                                    const words = label.split(' ');
                                    const mid = Math.ceil(words.length / 2);
                                    return [words.slice(0, mid).join(' '), words.slice(mid).join(' ')];
                                }
                                return label;
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 4,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 11,
                                family: 'Roboto, sans-serif'
                            },
                            color: '#6b7280'
                        },
                        grid: {
                            color: '#e5e7eb',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Khởi tạo biểu đồ chuyên cần
     */
    async initAttendanceChart() {
        const canvas = document.getElementById(this.config.attendanceChartId);
        if (!canvas) {
            console.error(`Canvas #${this.config.attendanceChartId} không tồn tại`);
            return;
        }

        // Fetch data
        const data = await this.fetchAttendanceData();

        // Destroy old chart if exists
        if (this.attendanceChart) {
            this.attendanceChart.destroy();
        }

        // Create chart
        const ctx = canvas.getContext('2d');
        this.attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: '% ngày học',
                        data: data.attended,
                        backgroundColor: '#2563eb',
                        borderRadius: 4,
                        barThickness: 50,
                        maxBarThickness: 70,
                    },
                    {
                        label: '% ngày nghỉ',
                        data: data.absent,
                        backgroundColor: '#eab308',
                        borderRadius: 4,
                        barThickness: 50,
                        maxBarThickness: 70,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            font: {
                                size: 11,
                                family: 'Roboto, sans-serif'
                            },
                            color: '#6b7280'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 6,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                family: 'Roboto, sans-serif'
                            },
                            color: '#6b7280',
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                // Wrap long labels
                                if (label.length > 15) {
                                    const words = label.split(' ');
                                    const mid = Math.ceil(words.length / 2);
                                    return [words.slice(0, mid).join(' '), words.slice(mid).join(' ')];
                                }
                                return label;
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 25,
                            font: {
                                size: 11,
                                family: 'Roboto, sans-serif'
                            },
                            color: '#6b7280',
                            callback: function(value) {
                                return value;
                            }
                        },
                        grid: {
                            color: '#e5e7eb',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Fetch dữ liệu GPA từ API
     */
    async fetchGPAData() {
        try {
            // Uncomment khi có API thực
            // const response = await fetch(`${this.config.gpaApiEndpoint}?semester=${this.currentSemester}`);
            // return await response.json();

            // Demo data
            return this.getDemoGPAData();
        } catch (error) {
            console.error('Lỗi khi fetch GPA data:', error);
            return this.getDemoGPAData();
        }
    }

    /**
     * Fetch dữ liệu chuyên cần từ API
     */
    async fetchAttendanceData() {
        try {
            // Uncomment khi có API thực
            // const response = await fetch(`${this.config.attendanceApiEndpoint}?semester=${this.currentSemester}`);
            // return await response.json();

            // Demo data
            return this.getDemoAttendanceData();
        } catch (error) {
            console.error('Lỗi khi fetch attendance data:', error);
            return this.getDemoAttendanceData();
        }
    }

    /**
     * Demo data cho GPA chart
     */
    getDemoGPAData() {
        return {
            labels: [
                'Công nghệ phần mềm',
                'Thiết kế cơ sở dữ liệu',
                'Pháp luật đại cương'
            ],
            values: [2.5, 4.0, 1.2]
        };
    }

    /**
     * Demo data cho Attendance chart
     */
    getDemoAttendanceData() {
        return {
            labels: [
                'Công nghệ phần mềm',
                'Thiết kế cơ sở dữ liệu',
                'Pháp luật đại cương'
            ],
            attended: [75, 100, 85],
            absent: [25, 0, 15]
        };
    }

    /**
     * Gắn event listeners
     */
    attachEventListeners() {
        // Dropdown semester cho GPA chart
        const gpaSelect = document.getElementById('gpa-semester-select');
        if (gpaSelect) {
            gpaSelect.addEventListener('change', (e) => {
                this.currentSemester = e.target.value;
                this.initGPAChart();
            });
        }

        // Dropdown semester cho Attendance chart
        const attendanceSelect = document.getElementById('attendance-semester-select');
        if (attendanceSelect) {
            attendanceSelect.addEventListener('change', (e) => {
                this.currentSemester = e.target.value;
                this.initAttendanceChart();
            });
        }
    }

    /**
     * Update biểu đồ với dữ liệu mới
     */
    async updateCharts(semester) {
        this.currentSemester = semester;
        await Promise.all([
            this.initGPAChart(),
            this.initAttendanceChart()
        ]);
    }

    /**
     * Destroy tất cả biểu đồ
     */
    destroy() {
        if (this.gpaChart) {
            this.gpaChart.destroy();
            this.gpaChart = null;
        }
        if (this.attendanceChart) {
            this.attendanceChart.destroy();
            this.attendanceChart = null;
        }
    }
}

// Export for use
window.StudentCharts = StudentCharts;

// Auto-init khi DOM ready
document.addEventListener('DOMContentLoaded', async () => {
    // Kiểm tra Chart.js đã load chưa
    if (typeof Chart === 'undefined') {
        console.error('Chart.js chưa được load. Vui lòng thêm Chart.js vào trang.');
        return;
    }

    // Kiểm tra các canvas có tồn tại không
    const gpaCanvas = document.getElementById('gpaChart');
    const attendanceCanvas = document.getElementById('attendanceChart');

    if (gpaCanvas || attendanceCanvas) {
        const chartSystem = new StudentCharts();
        await chartSystem.init();

        // Lưu instance để có thể dùng sau
        window.studentChartsInstance = chartSystem;
    }
});
