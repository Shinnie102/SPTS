/**
 * Student Alert Warning System
 * Hiển thị các cảnh báo học vụ cho sinh viên
 * - Cảnh báo chuyên cần thấp
 * - Cảnh báo điểm kiểm tra thấp
 * - Cảnh báo không đủ điểm đạt
 */

class StudentAlertWarning {
    constructor(config = {}) {
        this.config = {
            apiEndpoint: config.apiEndpoint || '/api/student/warnings',
            containerId: config.containerId || 'student-alert-container',
            storageKey: 'student_alert_dismissed',
            sessionKey: 'student_session_id',
            ...config
        };
        
        this.warnings = [];
        this.currentSessionId = this.getSessionId();
    }

    /**
     * Khởi tạo và hiển thị cảnh báo
     */
    async init() {
        try {
            // Fetch dữ liệu cảnh báo từ API
            await this.fetchWarnings();
            
            // Kiểm tra và hiển thị nếu có cảnh báo
            if (this.shouldShowWarnings()) {
                this.render();
                this.attachEventListeners();
            }
        } catch (error) {
            console.error('Lỗi khi khởi tạo cảnh báo:', error);
        }
    }

    /**
     * Lấy session ID hiện tại (dùng để track login session)
     */
    getSessionId() {
        let sessionId = sessionStorage.getItem(this.config.sessionKey);
        if (!sessionId) {
            sessionId = Date.now().toString();
            sessionStorage.setItem(this.config.sessionKey, sessionId);
        }
        return sessionId;
    }

    /**
     * Fetch dữ liệu cảnh báo từ backend
     * Có thể thay thế bằng data thực tế từ API
     */
    async fetchWarnings() {
        try {
            // Uncomment dòng dưới khi có API thực
            // const response = await fetch(this.config.apiEndpoint);
            // this.warnings = await response.json();
            
            // Demo data - Thay bằng API call thực tế
            this.warnings = await this.getDemoWarnings();
        } catch (error) {
            console.error('Lỗi khi fetch warnings:', error);
            this.warnings = [];
        }
    }

    /**
     * Demo data - Thay thế bằng API thực tế
     * API nên trả về format:
     * {
     *   hasViolations: boolean,
     *   warnings: [
     *     {
     *       type: 'attendance' | 'low_score' | 'fail_risk',
     *       message: string,
     *       severity: 'high' | 'medium' | 'low',
     *       subjects: [{ name: string, value: string }]
     *     }
     *   ]
     * }
     */
    async getDemoWarnings() {
        // Simulate API call
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    hasViolations: true,
                    warnings: [
                        {
                            type: 'attendance',
                            title: 'Cảnh báo chuyên cần',
                            message: 'Một hoặc nhiều môn học của bạn đang có tỷ lệ chuyên cần thấp hơn mức cho phép. Hãy chú ý điểm danh đầy đủ để tránh bị cấm thi.',
                            severity: 'high',
                            subjects: [
                                { name: 'Lập trình mạng', rate: '65%' },
                                { name: 'Cơ sở dữ liệu', rate: '70%' }
                            ]
                        },
                        {
                            type: 'low_score',
                            title: 'Cảnh báo điểm số thấp',
                            message: 'Một số môn học của bạn có điểm kiểm tra dưới mức trung bình. Hãy tập trung học tập và cải thiện điểm số.',
                            severity: 'medium',
                            subjects: [
                                { name: 'Toán rời rạc', score: '4.5' },
                                { name: 'Cấu trúc dữ liệu', score: '5.0' }
                            ]
                        },
                        {
                            type: 'fail_risk',
                            title: 'Cảnh báo nguy cơ không đạt',
                            message: 'Các môn học dưới đây có nguy cơ không đủ điểm đạt. Vui lòng liên hệ giảng viên hoặc đăng ký học cải thiện.',
                            severity: 'high',
                            subjects: [
                                { name: 'Mạng máy tính', status: 'Điểm thường xuyên: 3.5' }
                            ]
                        }
                    ]
                });
            }, 300);
        });
    }

    /**
     * Kiểm tra xem có nên hiển thị cảnh báo không
     */
    shouldShowWarnings() {
        // Không có vi phạm thì không hiển thị
        if (!this.warnings.hasViolations || !this.warnings.warnings || this.warnings.warnings.length === 0) {
            return false;
        }

        // Lấy danh sách đã dismiss từ localStorage
        const dismissed = this.getDismissedWarnings();
        
        // Nếu đã dismiss trong session hiện tại, không hiển thị
        if (dismissed.sessionId === this.currentSessionId && dismissed.warningHash === this.getWarningHash()) {
            return false;
        }

        // Hiển thị nếu:
        // 1. Session mới (logout/login lại)
        // 2. Có vi phạm mới (hash khác)
        return true;
    }

    /**
     * Tạo hash của warnings để so sánh
     */
    getWarningHash() {
        const str = JSON.stringify(this.warnings.warnings.map(w => ({
            type: w.type,
            subjects: w.subjects
        })));
        return this.simpleHash(str);
    }

    /**
     * Simple hash function
     */
    simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString();
    }

    /**
     * Lấy thông tin warnings đã dismiss
     */
    getDismissedWarnings() {
        try {
            const data = localStorage.getItem(this.config.storageKey);
            return data ? JSON.parse(data) : {};
        } catch {
            return {};
        }
    }

    /**
     * Lưu thông tin đã dismiss
     */
    saveDismissedWarnings() {
        const data = {
            sessionId: this.currentSessionId,
            warningHash: this.getWarningHash(),
            timestamp: Date.now()
        };
        localStorage.setItem(this.config.storageKey, JSON.stringify(data));
    }

    /**
     * Tạo HTML cho warning box
     */
    generateHTML() {
        const warningsHTML = this.warnings.warnings.map(warning => {
            const subjectsHTML = warning.subjects.map(subject => {
                const detail = subject.rate || subject.score || subject.status;
                return `<li><strong>${subject.name}</strong>: ${detail}</li>`;
            }).join('');

            return `
                <div class="alert-warning-item alert-${warning.severity}">
                    <div class="alert-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>${warning.title}</h3>
                    </div>
                    <p class="alert-message">${warning.message}</p>
                    ${warning.subjects && warning.subjects.length > 0 ? `
                        <div class="alert-subjects">
                            <ul>${subjectsHTML}</ul>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        return `
            <div class="student-alert-warning" id="student-alert-warning">
                <div class="alert-container">
                    <button class="alert-close-btn" id="alert-close-btn" aria-label="Đóng cảnh báo">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="alert-content">
                        ${warningsHTML}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Tạo CSS styles
     */
    generateStyles() {
        return `
            <style>
                .student-alert-warning {
                    width: 100%;
                    margin-bottom: 1.5rem;
                    animation: slideDown 0.4s ease-out;
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .alert-container {
                    position: relative;
                    background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
                    border: 2px solid #ff4444;
                    border-radius: 12px;
                    padding: 1.5rem;
                    box-shadow: 0 4px 12px rgba(255, 68, 68, 0.1);
                }

                .alert-close-btn {
                    position: absolute;
                    top: 1rem;
                    right: 1rem;
                    background: transparent;
                    border: none;
                    font-size: 1.5rem;
                    color: #ff4444;
                    cursor: pointer;
                    width: 2rem;
                    height: 2rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                }

                .alert-close-btn:hover {
                    background-color: rgba(255, 68, 68, 0.1);
                    transform: rotate(90deg);
                }

                .alert-content {
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }

                .alert-warning-item {
                    background: white;
                    border-radius: 8px;
                    padding: 1.25rem;
                    border-left: 4px solid #ff4444;
                }

                .alert-warning-item.alert-high {
                    border-left-color: #ff4444;
                }

                .alert-warning-item.alert-medium {
                    border-left-color: #ff9800;
                }

                .alert-warning-item.alert-low {
                    border-left-color: #ffc107;
                }

                .alert-header {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    margin-bottom: 0.75rem;
                }

                .alert-header i {
                    font-size: 1.5rem;
                    color: #ff4444;
                }

                .alert-header h3 {
                    font-size: 1.125rem;
                    font-weight: 600;
                    color: #d32f2f;
                    margin: 0;
                }

                .alert-message {
                    color: #c62828;
                    font-size: 0.95rem;
                    line-height: 1.6;
                    margin: 0 0 1rem 0;
                }

                .alert-subjects {
                    background: #fff8f8;
                    border-radius: 6px;
                    padding: 0.875rem;
                }

                .alert-subjects ul {
                    margin: 0;
                    padding-left: 1.25rem;
                    color: #b71c1c;
                }

                .alert-subjects li {
                    margin-bottom: 0.5rem;
                    font-size: 0.9rem;
                }

                .alert-subjects li:last-child {
                    margin-bottom: 0;
                }

                .alert-subjects strong {
                    color: #d32f2f;
                }

                /* Animation khi đóng */
                .student-alert-warning.closing {
                    animation: slideUp 0.3s ease-out forwards;
                }

                @keyframes slideUp {
                    from {
                        opacity: 1;
                        transform: translateY(0);
                        max-height: 500px;
                    }
                    to {
                        opacity: 0;
                        transform: translateY(-20px);
                        max-height: 0;
                        margin-bottom: 0;
                    }
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .alert-container {
                        padding: 1rem;
                    }

                    .alert-close-btn {
                        top: 0.75rem;
                        right: 0.75rem;
                        font-size: 1.25rem;
                    }

                    .alert-header h3 {
                        font-size: 1rem;
                    }

                    .alert-message {
                        font-size: 0.875rem;
                    }

                    .alert-subjects li {
                        font-size: 0.85rem;
                    }
                }
            </style>
        `;
    }

    /**
     * Render alert vào DOM
     */
    render() {
        const container = document.getElementById(this.config.containerId);
        if (!container) {
            console.error(`Container #${this.config.containerId} không tồn tại`);
            return;
        }

        // Inject styles nếu chưa có
        if (!document.getElementById('student-alert-styles')) {
            const styleTag = document.createElement('div');
            styleTag.id = 'student-alert-styles';
            styleTag.innerHTML = this.generateStyles();
            document.head.appendChild(styleTag);
        }

        // Inject HTML
        container.innerHTML = this.generateHTML();
    }

    /**
     * Gắn event listeners
     */
    attachEventListeners() {
        const closeBtn = document.getElementById('alert-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeAlert());
        }
    }

    /**
     * Đóng alert
     */
    closeAlert() {
        const alertElement = document.getElementById('student-alert-warning');
        if (alertElement) {
            // Add closing animation
            alertElement.classList.add('closing');
            
            // Lưu trạng thái đã đóng
            this.saveDismissedWarnings();
            
            // Remove khỏi DOM sau animation
            setTimeout(() => {
                alertElement.remove();
            }, 300);
        }
    }

    /**
     * Force refresh - dùng khi cần hiển thị lại ngay
     */
    async forceRefresh() {
        localStorage.removeItem(this.config.storageKey);
        await this.init();
    }

    /**
     * Clear dismissed state - dùng khi logout
     */
    static clearDismissedState() {
        localStorage.removeItem('student_alert_dismissed');
        sessionStorage.removeItem('student_session_id');
    }
}

// Export cho sử dụng
window.StudentAlertWarning = StudentAlertWarning;

// Auto-init nếu có container trong page
document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('student-alert-container');
    if (container) {
        const alertSystem = new StudentAlertWarning();
        await alertSystem.init();
        
        // Lưu instance để có thể dùng sau
        window.studentAlertInstance = alertSystem;
    }
});
