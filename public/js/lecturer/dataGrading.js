const ApiService = (() => {
  let cachedGrading = null;

  // TEMP: allow lecturer to save grading structure via existing endpoint.
  // Flip to false later to disable without touching grading.js.
  const ENABLE_STRUCTURE_SAVE = true;

  const getCsrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  };

  const getClassIdFromUrl = () => {
    const path = window.location.pathname || '';
    const match = path.match(/\/lecturer\/class\/(\d+)\/grading/);
    return match ? parseInt(match[1], 10) : null;
  };

  const fetchJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...options
    });

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
      const message = (data && (data.message || data.error)) ? (data.message || data.error) : `HTTP ${response.status}`;
      return {
        success: false,
        message,
        errors: data && data.errors ? data.errors : undefined,
        status: response.status
      };
    }

    return data;
  };

  const getGradingData = async (force = false) => {
    if (cachedGrading && !force) return cachedGrading;

    const classId = getClassIdFromUrl();
    if (!classId) {
      return { success: false, message: 'Không xác định được lớp học phần.' };
    }

    const result = await fetchJson(`/lecturer/class/${classId}/grading-data`);
    // Only cache successful payloads (avoid caching error objects)
    if (result && !result.success && result.message) {
      return result;
    }
    cachedGrading = result;
    return result;
  };

  const save = async (payload) => {
    const classId = getClassIdFromUrl();
    if (!classId) {
      return { success: false, message: 'Không xác định được lớp học phần.' };
    }

    const result = await fetchJson(`/lecturer/class/${classId}/grading/save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (result && result.success) cachedGrading = null;

    return result;
  };

  return {
    getGradingData,

    getStructureData: async () => {
      const result = await getGradingData();
      if (!result || result.message) return { success: false, message: result?.message || 'Có lỗi xảy ra' };
      return { success: true, data: result.structure || [] };
    },

    getGradeData: async () => {
      const result = await getGradingData();
      if (!result || result.message) return { success: false, message: result?.message || 'Có lỗi xảy ra' };
      // Convert API (students + scores) into a single bundle for grading.js to map
      return { success: true, data: { students: result.students || [], scores: result.scores || [], isLocked: !!result.isLocked } };
    },

    saveStructureData: async (structure) => {
      if (!ENABLE_STRUCTURE_SAVE) {
        return { success: false, message: 'Tính năng lưu cấu trúc điểm đang tạm tắt.' };
      }
      return save({ structure });
    },
    saveGradeData: async (scores) => save({ scores }),

    exportGradeData: async () => {
      const result = await getGradingData();
      if (!result || result.message) return { success: false, message: result?.message || 'Có lỗi xảy ra' };

      const exportData = {
        structure: result.structure || [],
        students: result.students || [],
        scores: result.scores || [],
        exportDate: new Date().toISOString()
      };

      const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
      return {
        success: true,
        blob,
        filename: `bang-diem-${new Date().toISOString().split('T')[0]}.json`
      };
    }
  };
})();

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { ApiService };
}