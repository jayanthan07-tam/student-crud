/**
 * Academic Operations System - Student CRUD
 * Vanilla JavaScript (Beginner-Friendly, Modular, No Frameworks)
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Sidebar Toggle
  const mobileToggleBtn = document.getElementById('mobileMenuToggle');
  const sidebar = document.querySelector('.sidebar');

  if (mobileToggleBtn && sidebar) {
    mobileToggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (event) {
      if (
        sidebar.classList.contains('open') &&
        !sidebar.contains(event.target) &&
        !mobileToggleBtn.contains(event.target)
      ) {
        sidebar.classList.remove('open');
      }
    });
  }

  // 2. Real-Time Table Search Filter (Client-side interactive helper)
  const searchInput = document.getElementById('studentTableSearch');
  const studentTable = document.querySelector('.data-table tbody');

  if (searchInput && studentTable) {
    searchInput.addEventListener('input', function (e) {
      const query = e.target.value.toLowerCase().trim();
      const rows = studentTable.querySelectorAll('tr');

      rows.forEach(function (row) {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }

  // 3. Delete Confirmation Dialog Helper (Section 08)
  const deleteTriggers = document.querySelectorAll('.js-delete-trigger');
  const deleteModal = document.getElementById('deleteModal');
  const deleteStudentIdInput = document.getElementById('deleteStudentIdInput');
  const studentDeleteName = document.getElementById('studentDeleteName');
  const studentDeleteReg = document.getElementById('studentDeleteReg');

  if (deleteTriggers.length > 0 && deleteModal) {
    deleteTriggers.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const studentId = this.getAttribute('data-id');
        const studentName = this.getAttribute('data-name') || 'this student';
        const studentReg = this.getAttribute('data-reg') || '';

        if (studentDeleteName) {
          studentDeleteName.textContent = studentName;
        }

        if (studentDeleteReg) {
          studentDeleteReg.textContent = studentReg;
        }

        if (deleteStudentIdInput) {
          deleteStudentIdInput.value = studentId;
        }

        deleteModal.style.display = 'flex';
      });
    });

    // Close on backdrop click
    deleteModal.addEventListener('click', function (e) {
      if (e.target === deleteModal) {
        deleteModal.style.display = 'none';
      }
    });

    // Close on ESC key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
        deleteModal.style.display = 'none';
      }
    });
  }

  // Modal dismiss buttons
  const modalDismissButtons = document.querySelectorAll('.js-modal-dismiss');
  modalDismissButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      const modal = this.closest('.modal-overlay');
      if (modal) {
        modal.style.display = 'none';
      }
    });
  });

  // 4. Global Topbar Quick Search (Routes to Student Directory Search on Enter)
  const globalQuickSearch = document.getElementById('globalQuickSearch');
  if (globalQuickSearch) {
    globalQuickSearch.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const term = globalQuickSearch.value.trim();
        window.location.href = term ? ('students.php?search=' + encodeURIComponent(term)) : 'students.php';
      }
    });
  }

  // 5. Auto-update Current Date in Header
  const dateIndicator = document.getElementById('currentDateIndicator');
  if (dateIndicator) {
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    dateIndicator.textContent = new Date().toLocaleDateString('en-US', options);
  }

  // 6. Notification Bell Dropdown Toggle
  const notifToggleBtn = document.getElementById('notificationToggleBtn');
  const notifDropdown = document.getElementById('notificationDropdownMenu');
  const notifWrapper = document.getElementById('notificationDropdownWrap');

  if (notifToggleBtn && notifDropdown) {
    notifToggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      notifDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
      if (notifWrapper && !notifWrapper.contains(e.target)) {
        notifDropdown.classList.remove('open');
      }
    });
  }

  // 7. Interactive Chart.js Performance Overview Chart (Section 05)
  const chartCanvas = document.getElementById('performanceChart');
  if (chartCanvas && typeof Chart !== 'undefined') {
    try {
      const rawLabels = chartCanvas.getAttribute('data-labels') || '[]';
      const rawValues = chartCanvas.getAttribute('data-values') || '[]';
      const labels = JSON.parse(rawLabels);
      const values = JSON.parse(rawValues);

      const ctx = chartCanvas.getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Students in Band',
            data: values,
            backgroundColor: [
              'rgba(239, 68, 68, 0.85)',   // 0-40% (Red/Probation)
              'rgba(245, 158, 11, 0.85)',  // 40-50% (Amber/Average)
              'rgba(99, 102, 241, 0.85)',  // 50-60% (Indigo/Standard)
              'rgba(79, 70, 229, 0.90)',   // 60-70% (Deep Indigo/Good)
              'rgba(16, 185, 129, 0.85)',  // 70-80% (Emerald/Distinction)
              'rgba(13, 148, 136, 0.90)'   // 80-100% (Teal/Excellence)
            ],
            borderColor: [
              '#dc2626',
              '#d97706',
              '#4f46e5',
              '#4338ca',
              '#059669',
              '#0f766e'
            ],
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.65,
            categoryPercentage: 0.8
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
              backgroundColor: '#0f172a',
              titleColor: '#f8fafc',
              bodyColor: '#e2e8f0',
              padding: 10,
              cornerRadius: 8,
              callbacks: {
                label: function (context) {
                  const count = context.parsed.y;
                  return ` ${count} student${count === 1 ? '' : 's'}`;
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
                  family: 'Inter, sans-serif',
                  size: 11,
                  weight: 500
                },
                color: '#64748b'
              }
            },
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                precision: 0,
                font: {
                  family: 'Inter, sans-serif',
                  size: 11
                },
                color: '#94a3b8'
              },
              grid: {
                color: '#f1f5f9',
                drawBorder: false
              }
            }
          }
        }
      });
    } catch (err) {
      console.warn('Could not initialize Chart.js distribution chart:', err);
    }
  }

  // 8. Sections 07 & 08: Add/Edit Student Pages - Dynamic Age, Attendance Toggle, Live Percentage & Client Validation
  const dobInput = document.getElementById('dob');
  const ageDisplayInput = document.getElementById('ageDisplay') || document.getElementById('calculated_age');

  // 8.1 Auto-calculate Age from Date of Birth (Not stored in database)
  function updateCalculatedAge() {
    if (!dobInput || !ageDisplayInput) return;
    const dobVal = dobInput.value;
    if (!dobVal) {
      ageDisplayInput.value = '';
      return;
    }
    const birthDate = new Date(dobVal + 'T00:00:00');
    if (isNaN(birthDate.getTime())) {
      ageDisplayInput.value = 'Invalid Date';
      return;
    }
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    ageDisplayInput.value = age >= 0 ? age + ' yrs' : 'Invalid Date';
  }

  if (dobInput) {
    dobInput.addEventListener('change', updateCalculatedAge);
    dobInput.addEventListener('input', updateCalculatedAge);
    updateCalculatedAge();
  }

  // 8.2 Live Subject Marks Attendance Toggle & Percentage Calculation
  const marksTable = document.querySelector('.marks-entry-table');
  const previewPctBadge = document.getElementById('previewPctBadge');
  const previewPctBar = document.getElementById('previewPctBar');
  const previewAttendedStats = document.getElementById('previewAttendedStats');

  // Edit page preview elements
  const previewPctDisplay = document.getElementById('previewPctDisplay');
  const previewStatusDisplay = document.getElementById('previewStatusDisplay');
  const previewProgressFill = document.getElementById('previewProgressFill');
  const previewCountSummary = document.getElementById('previewCountSummary');
  const previewTierSummary = document.getElementById('previewTierSummary');

  function recalculateLivePercentage() {
    if (!marksTable || (!previewPctBadge && !previewPctDisplay)) return;

    const rows = marksTable.querySelectorAll('tbody tr');
    let totalObtained = 0;
    let totalAttendedMax = 0;
    let presentCount = 0;
    let absentCount = 0;
    let hasIncompletePresent = false;

    rows.forEach(function (row) {
      const attendanceSelect = row.querySelector('.attendance-select');
      const marksInput = row.querySelector('.marks-input');
      const maxMarks = parseFloat(marksInput ? marksInput.getAttribute('max') || '100' : '100');

      if (!attendanceSelect || !marksInput) return;

      const status = attendanceSelect.value;
      if (status === 'Absent') {
        absentCount++;
        row.classList.add('row-absent');
        marksInput.disabled = true;
        marksInput.required = false;
        marksInput.value = '';
        marksInput.placeholder = 'Absent (NULL)';
      } else {
        presentCount++;
        row.classList.remove('row-absent');
        marksInput.disabled = false;
        marksInput.required = true;
        marksInput.placeholder = '0 - ' + maxMarks;

        const valStr = marksInput.value.trim();
        if (valStr === '') {
          hasIncompletePresent = true;
        } else {
          const valNum = parseFloat(valStr);
          if (!isNaN(valNum) && valNum >= 0 && valNum <= maxMarks) {
            totalObtained += valNum;
            totalAttendedMax += maxMarks;
          } else {
            hasIncompletePresent = true;
          }
        }
      }
    });

    if (previewAttendedStats) {
      previewAttendedStats.textContent = `Present in ${presentCount} / ${rows.length} subjects • Absent in ${absentCount} subject${absentCount === 1 ? '' : 's'}`;
    }
    if (previewCountSummary) {
      previewCountSummary.textContent = `Attended: ${presentCount} / ${rows.length} • Absent: ${absentCount}`;
    }

    if (presentCount === 0 && absentCount === rows.length) {
      if (previewPctBadge) {
        previewPctBadge.className = 'badge-status status-withdrawn';
        previewPctBadge.innerHTML = '<span class="badge-dot"></span>Absent';
      }
      if (previewPctBar) {
        previewPctBar.style.width = '0%';
        previewPctBar.style.backgroundColor = '#94a3b8';
      }
      if (previewPctDisplay) previewPctDisplay.textContent = 'Absent';
      if (previewStatusDisplay) previewStatusDisplay.textContent = 'All Subjects Absent';
      if (previewProgressFill) {
        previewProgressFill.style.width = '0%';
        previewProgressFill.style.backgroundColor = '#94a3b8';
      }
      if (previewTierSummary) previewTierSummary.textContent = 'Excluded from Cohort Average';
    } else if (hasIncompletePresent || presentCount === 0) {
      if (previewPctBadge) {
        previewPctBadge.className = 'badge-status status-neutral';
        previewPctBadge.innerHTML = '<span class="badge-dot"></span>Not Available';
      }
      if (previewPctBar) {
        previewPctBar.style.width = '0%';
        previewPctBar.style.backgroundColor = '#cbd5e1';
      }
      if (previewPctDisplay) previewPctDisplay.textContent = '--';
      if (previewStatusDisplay) previewStatusDisplay.textContent = 'Awaiting Marks Input';
      if (previewProgressFill) {
        previewProgressFill.style.width = '0%';
        previewProgressFill.style.backgroundColor = '#cbd5e1';
      }
      if (previewTierSummary) previewTierSummary.textContent = 'Not Available';
    } else {
      const pct = (totalObtained / totalAttendedMax) * 100;
      const formattedPct = pct.toFixed(2);

      let badgeClass = 'status-danger';
      let tierLabel = 'Academic Review (< 40%)';
      let barColor = '#ef4444';

      if (pct >= 70) {
        badgeClass = 'status-active';
        tierLabel = 'Distinction (70%+)';
        barColor = '#10b981';
      } else if (pct >= 60) {
        badgeClass = 'status-active';
        tierLabel = 'First Class (60–69%)';
        barColor = '#6366f1';
      } else if (pct >= 50) {
        badgeClass = 'status-active';
        tierLabel = 'Second Class (50–59%)';
        barColor = '#3b82f6';
      } else if (pct >= 40) {
        badgeClass = 'status-warning';
        tierLabel = 'Pass Class (40–49%)';
        barColor = '#f59e0b';
      }

      if (previewPctBadge) {
        previewPctBadge.className = 'badge-status ' + badgeClass;
        previewPctBadge.innerHTML = `<span class="badge-dot"></span>${formattedPct}% &bull; ${tierLabel}`;
      }
      if (previewPctBar) {
        previewPctBar.style.width = Math.min(100, Math.max(0, pct)) + '%';
        previewPctBar.style.backgroundColor = barColor;
      }
      if (previewPctDisplay) previewPctDisplay.textContent = formattedPct + '%';
      if (previewStatusDisplay) previewStatusDisplay.textContent = `${totalObtained.toFixed(2).replace(/\.00$/, '')} / ${totalAttendedMax} Attended Marks`;
      if (previewProgressFill) {
        previewProgressFill.style.width = Math.min(100, Math.max(0, pct)) + '%';
        previewProgressFill.style.backgroundColor = barColor;
      }
      if (previewTierSummary) previewTierSummary.textContent = tierLabel;
    }
  }

  if (marksTable) {
    marksTable.addEventListener('change', function (e) {
      if (e.target.classList.contains('attendance-select') || e.target.classList.contains('marks-input')) {
        recalculateLivePercentage();
      }
    });

    marksTable.addEventListener('input', function (e) {
      if (e.target.classList.contains('marks-input')) {
        recalculateLivePercentage();
      }
    });

    recalculateLivePercentage();
  }

  // 8.3 Frontend Form Validation (for both Add and Edit Student forms)
  const targetForm = document.getElementById('addStudentForm') || document.getElementById('editStudentForm');
  if (targetForm) {
    targetForm.addEventListener('submit', function (e) {
      let hasError = false;
      const clearError = (input) => {
        input.classList.remove('input-error');
        const next = input.nextElementSibling;
        if (next && next.classList.contains('field-error-msg')) {
          next.remove();
        }
      };
      const setError = (input, msg) => {
        clearError(input);
        input.classList.add('input-error');
        const err = document.createElement('div');
        err.className = 'field-error-msg';
        err.textContent = msg;
        input.parentNode.appendChild(err);
        if (!hasError) {
          input.focus();
          hasError = true;
        }
      };

      const nameInput = document.getElementById('student_name');
      if (nameInput) {
        clearError(nameInput);
        if (!nameInput.value.trim()) {
          setError(nameInput, 'Student Name is required.');
        } else if (nameInput.value.trim().length < 2) {
          setError(nameInput, 'Student Name must be at least 2 characters.');
        }
      }

      const regInput = document.getElementById('register_no');
      if (regInput) {
        clearError(regInput);
        if (!regInput.value.trim()) {
          setError(regInput, 'Register Number is required.');
        } else if (!/^[A-Za-z0-9_-]{3,30}$/.test(regInput.value.trim())) {
          setError(regInput, 'Register Number format is invalid (3–30 alphanumeric characters).');
        }
      }

      const mobileInput = document.getElementById('mobile_number');
      if (mobileInput) {
        clearError(mobileInput);
        const mobVal = mobileInput.value.trim();
        if (!mobVal) {
          setError(mobileInput, 'Mobile Number is required.');
        } else if (!/^[0-9]{10}$/.test(mobVal)) {
          setError(mobileInput, 'Mobile Number must be exactly 10 digits (e.g. 9876500001).');
        }
      }

      if (dobInput) {
        clearError(dobInput);
        if (!dobInput.value) {
          setError(dobInput, 'Date of Birth is required.');
        } else {
          const bDate = new Date(dobInput.value);
          const now = new Date();
          if (bDate > now) {
            setError(dobInput, 'Date of Birth cannot be in the future.');
          }
        }
      }

      if (marksTable) {
        const rows = marksTable.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
          const attendanceSelect = row.querySelector('.attendance-select');
          const marksInput = row.querySelector('.marks-input');
          if (attendanceSelect && marksInput) {
            clearError(marksInput);
            if (attendanceSelect.value === 'Present') {
              const valStr = marksInput.value.trim();
              const maxVal = parseFloat(marksInput.getAttribute('max') || '100');
              if (valStr === '') {
                setError(marksInput, 'Marks obtained is required for Present subjects.');
              } else {
                const val = parseFloat(valStr);
                if (isNaN(val) || val < 0 || val > maxVal) {
                  setError(marksInput, `Marks must be between 0 and ${maxVal}.`);
                }
              }
            }
          }
        });
      }

      if (hasError) {
        e.preventDefault();
      } else {
        setTimeout(function () {
          const submitButtons = targetForm.querySelectorAll('button[type="submit"]');
          submitButtons.forEach(function (btn) {
            btn.disabled = true;
          });
        }, 0);
      }
    });
  }

  // 9. Section 09: Student Performance Dashboard Charts (Chart.js)
  // 9.1 Percentage Distribution Chart
  const distCanvas = document.getElementById('performanceDistributionChart');
  if (distCanvas && typeof Chart !== 'undefined') {
    try {
      const dataPayload = JSON.parse(distCanvas.getAttribute('data-chart') || '{}');
      new Chart(distCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: dataPayload.labels || [],
          datasets: [{
            label: 'Students in Band',
            data: dataPayload.values || [],
            backgroundColor: [
              'rgba(239, 68, 68, 0.85)',   // 0-40%
              'rgba(245, 158, 11, 0.85)',  // 40-50%
              'rgba(59, 130, 246, 0.85)',  // 50-60%
              'rgba(99, 102, 241, 0.85)',  // 60-70%
              'rgba(79, 70, 229, 0.85)',   // 70-80%
              'rgba(16, 185, 129, 0.90)'   // 80-100%
            ],
            borderColor: [
              '#dc2626', '#d97706', '#2563eb', '#4f46e5', '#4338ca', '#059669'
            ],
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => ` ${ctx.parsed.y} Student${ctx.parsed.y === 1 ? '' : 's'}`
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0, font: { size: 11 } },
              grid: { color: 'rgba(226, 232, 240, 0.6)' }
            },
            x: {
              grid: { display: false },
              ticks: { font: { size: 10, weight: '500' } }
            }
          }
        }
      });
    } catch (e) {
      console.error('Error rendering performanceDistributionChart:', e);
    }
  }

  // 9.2 Top 5 Students Bar Chart
  const topCanvas = document.getElementById('topStudentsChart');
  if (topCanvas && typeof Chart !== 'undefined') {
    try {
      const topPayload = JSON.parse(topCanvas.getAttribute('data-chart') || '{}');
      new Chart(topCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: (topPayload.labels || []).map(name => {
            const parts = name.split(' ');
            return parts.length > 2 ? parts.slice(0, 2).join(' ') : name;
          }),
          datasets: [{
            label: 'Calculated Percentage',
            data: topPayload.values || [],
            backgroundColor: 'rgba(16, 185, 129, 0.85)',
            borderColor: '#059669',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => ` ${ctx.parsed.x.toFixed(2)}%`
              }
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              max: 100,
              ticks: {
                callback: (v) => v + '%',
                font: { size: 11 }
              },
              grid: { color: 'rgba(226, 232, 240, 0.6)' }
            },
            y: {
              grid: { display: false },
              ticks: { font: { size: 10, weight: '600' } }
            }
          }
        }
      });
    } catch (e) {
      console.error('Error rendering topStudentsChart:', e);
    }
  }

  // 9.3 Subject-wise Average Marks Chart
  const subjCanvas = document.getElementById('subjectAveragesChart');
  if (subjCanvas && typeof Chart !== 'undefined') {
    try {
      const subjPayload = JSON.parse(subjCanvas.getAttribute('data-chart') || '{}');
      new Chart(subjCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: subjPayload.labels || [],
          datasets: [{
            label: 'Average Mark',
            data: subjPayload.values || [],
            backgroundColor: 'rgba(99, 102, 241, 0.85)',
            borderColor: '#4f46e5',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: (items) => {
                  const idx = items[0].dataIndex;
                  return (subjPayload.fullLabels && subjPayload.fullLabels[idx]) || items[0].label;
                },
                label: (ctx) => ` Average: ${ctx.parsed.y.toFixed(2)} / 100`
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              ticks: { font: { size: 11 } },
              grid: { color: 'rgba(226, 232, 240, 0.6)' }
            },
            x: {
              grid: { display: false },
              ticks: { font: { size: 10, weight: '500' } }
            }
          }
        }
      });
    } catch (e) {
      console.error('Error rendering subjectAveragesChart:', e);
    }
  }
});
