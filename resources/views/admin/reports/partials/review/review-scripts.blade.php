@push('scripts')
<script>
(() => {
  const root = document.querySelector('[data-review-workflow]');
  if (!root) return;

  const form = document.getElementById('review-form');
  const stateMessage = root.querySelector('[data-ai-state-message]');
  const aiStateBadge = root.querySelector('[data-ai-state-badge]');
  const confidenceWrap = root.querySelector('[data-ai-confidence-wrap]');
  const confidenceBadge = root.querySelector('[data-ai-confidence]');
  const lowConfidenceBadge = root.querySelector('[data-low-confidence]');
  const acceptAllButton = root.querySelector('[data-accept-all]');
  const routeButton = root.querySelector('[data-route-submit]');
  const referralSubmit = root.querySelector('[data-referral-submit]');
  const countLabel = root.querySelector('[data-reviewed-count]');
  const gateReason = root.querySelector('[data-gate-reason]');
  const pollUrl = root.dataset.aiPollUrl;
  let aiState = root.dataset.aiState;
  let pollAttempts = 0;
  let officeTouched = false;
  let currentWorkspaceStep = Number(root.dataset.defaultStep || 1);

  const normalize = (value) => String(value ?? '').trim().toLocaleLowerCase();
  const rows = [...root.querySelectorAll('[data-review-row]')];

  // Dossier Tabs
  root.querySelectorAll('[data-dossier-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.dossierTab;
      root.querySelectorAll('[data-dossier-tab]').forEach((t) => {
        const active = t === tab;
        t.setAttribute('aria-selected', active ? 'true' : 'false');
        t.className = active
          ? 'py-3 px-2 border-b-2 border-blue-600 text-blue-700 bg-white flex items-center justify-center gap-1.5 transition'
          : 'py-3 px-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 transition';
      });
      root.querySelectorAll('[data-dossier-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.dossierPanel !== target);
      });
      if (target === 'evidence') {
        window.setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
      }
    });
  });

  // Dossier Original Description 2-line Clamp Toggle
  const origDesc = document.getElementById('dossier-orig-desc');
  const origToggle = document.getElementById('dossier-desc-toggle');
  if (origDesc && origToggle) {
    if (origDesc.scrollHeight > origDesc.clientHeight + 2) {
      origToggle.classList.remove('hidden');
      origToggle.addEventListener('click', () => {
        const expanded = origDesc.classList.toggle('is-expanded');
        origToggle.textContent = expanded ? 'View less' : 'View more';
      });
    }
  }

  // Photo Lightbox
  const lightboxModal = document.getElementById('photo-lightbox-modal');
  const lightboxImg = document.getElementById('photo-lightbox-img');
  root.querySelectorAll('[data-lightbox-src]').forEach((img) => {
    img.addEventListener('click', () => {
      if (lightboxModal && lightboxImg) {
        lightboxImg.src = img.dataset.lightboxSrc;
        lightboxModal.showModal();
      }
    });
  });

  // 3-Step Workspace Stepper
  const setWorkspaceStep = (step) => {
    currentWorkspaceStep = step;
    root.querySelectorAll('[data-workspace-panel]').forEach((panel) => {
      panel.hidden = Number(panel.dataset.workspacePanel) !== step;
    });
    root.querySelectorAll('[data-workspace-step]').forEach((btn) => {
      const s = Number(btn.dataset.workspaceStep);
      const dot = btn.querySelector('.rw-step-dot');
      const title = btn.querySelector('span > span:first-child');
      if (s < step || (root.dataset.isAfterRoute === 'true' && s < 3 && s !== step)) {
        dot.className = 'rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-emerald-600 text-white transition-all duration-200';
        dot.innerHTML = '<i class="fa-solid fa-check text-[10px]" aria-hidden="true"></i>';
        if (title) title.className = 'block text-xs sm:text-sm font-bold text-slate-800';
      } else if (s === step) {
        dot.className = 'rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-blue-600 text-white ring-4 ring-blue-100 transition-all duration-200';
        dot.textContent = String(s);
        if (title) title.className = 'block text-xs sm:text-sm font-bold text-slate-900';
      } else {
        dot.className = 'rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-white text-slate-500 border border-slate-300 transition-all duration-200';
        dot.textContent = String(s);
        if (title) title.className = 'block text-xs sm:text-sm font-bold text-slate-500';
      }
    });
    const c1 = document.getElementById('rw-connector-1');
    const c2 = document.getElementById('rw-connector-2');
    if (c1) c1.style.width = step >= 2 ? '100%' : '0%';
    if (c2) c2.style.width = step >= 3 ? '100%' : '0%';
  };

  // Urgency 1-Tap Pill Sync
  const urgencySelect = document.getElementById('final-urgency');
  const syncUrgencyPills = () => {
    const val = urgencySelect?.value || '';
    root.querySelectorAll('[data-urgency-pill-btn]').forEach((btn) => {
      const level = btn.dataset.urgencyPillBtn;
      const active = val === level;
      let activeCls = 'bg-slate-700 text-white shadow-xs';
      if (level === 'Medium') activeCls = 'bg-blue-600 text-white shadow-xs';
      if (level === 'High') activeCls = 'bg-amber-500 text-white shadow-xs';
      if (level === 'Critical') activeCls = 'bg-rose-600 text-white shadow-xs';
      btn.className = active
        ? `py-1.5 px-1 rounded-md text-xs font-bold transition text-center ${activeCls}`
        : 'py-1.5 px-1 rounded-md text-xs font-bold transition text-center text-slate-600 hover:text-slate-900';
    });
  };
  root.querySelectorAll('[data-urgency-pill-btn]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!urgencySelect || urgencySelect.disabled) return;
      urgencySelect.value = btn.dataset.urgencyPillBtn;
      urgencySelect.dispatchEvent(new Event('change', { bubbles: true }));
      syncUrgencyPills();
    });
  });

  const stateClass = (state) => ({
    'AI suggested': 'review-badge-blue',
    Accepted: 'review-badge-green',
    Edited: 'review-badge-amber',
    Resident: 'review-badge-gray',
  }[state] || 'review-badge-gray');

  const RESOLVED_STATES = ['Accepted', 'Resident', 'Edited'];
  const rowState = (row) => row.querySelector('[data-final-state]')?.textContent.trim() || '';
  const isRowResolved = (row) => RESOLVED_STATES.includes(rowState(row));

  const setResolvedUi = (row) => {
    const resolved = isRowResolved(row);
    const undo = row.querySelector('[data-undo-row]');
    if (undo) undo.hidden = !resolved;
    if (!resolved) return false;
    const comparisonBadge = row.querySelector('[data-comparison-badge]');
    const useAi = row.querySelector('[data-use-ai]');
    const accept = row.querySelector('[data-accept-row]');
    const useResident = row.querySelector('[data-use-resident]');
    if (comparisonBadge) comparisonBadge.hidden = true;
    if (useAi) useAi.hidden = true;
    if (accept) accept.hidden = true;
    if (useResident) useResident.hidden = true;
    return true;
  };

  const snapshotUndo = (row) => {
    if (isRowResolved(row)) return;
    const control = row.querySelector('[data-final-control]');
    row.dataset.undoValue = control ? control.value : '';
    row.dataset.undoState = rowState(row) || 'AI suggested';
  };

  const setFinalState = (row, state) => {
    const badge = row.querySelector('[data-final-state]');
    if (!badge) return;
    badge.textContent = state;
    badge.className = `review-badge ${stateClass(state)}`;
    row.dataset.reviewed = 'true';
    setResolvedUi(row);
  };

  const updateRow = (row) => {
    const aiPresent = row.dataset.aiPresent === 'true';
    if (setResolvedUi(row)) return;
    const comparisonBadge = row.querySelector('[data-comparison-badge]');
    const aiDisplay = row.querySelector('[data-ai-value-display]');
    const useAi = row.querySelector('[data-use-ai]');
    const accept = row.querySelector('[data-accept-row]');
    const useResident = row.querySelector('[data-use-resident]');

    if (aiState === 'pending') {
      if (aiDisplay) aiDisplay.textContent = 'Waiting for suggestion';
      if (comparisonBadge) comparisonBadge.hidden = true;
      if (useAi) useAi.hidden = true;
      if (accept) accept.hidden = true;
      if (useResident) useResident.hidden = true;
      return;
    }
    if (aiState === 'unavailable') {
      if (aiDisplay) aiDisplay.textContent = 'Not available';
      if (comparisonBadge) comparisonBadge.hidden = true;
      if (useAi) useAi.hidden = true;
      if (accept) accept.hidden = true;
      if (useResident) useResident.hidden = true;
      row.dataset.reviewRequired = 'false';
      return;
    }
    if (!aiPresent) {
      if (comparisonBadge) comparisonBadge.hidden = true;
      if (useAi) useAi.hidden = true;
      if (accept) accept.hidden = true;
      if (useResident) useResident.hidden = true;
      return;
    }

    const residentValue = row.dataset.residentValue || '';
    const aiValue = row.dataset.aiValue || '';
    const differs = residentValue === '' || normalize(residentValue) !== normalize(aiValue);
    if (comparisonBadge) {
      comparisonBadge.hidden = false;
      comparisonBadge.textContent = residentValue === '' ? 'AI only' : (differs ? 'Differs' : 'Matches');
      comparisonBadge.className = `review-badge ${residentValue === '' ? 'review-badge-outline-blue' : (differs ? 'review-badge-outline-amber' : 'review-badge-outline-green')}`;
    }
    if (useAi) useAi.hidden = !differs || aiValue === '';
    if (accept) accept.hidden = aiValue === '';
    if (useResident) useResident.hidden = residentValue === '' || !differs;
  };

  const renderDescriptionDiff = (row) => {
    const container = row.querySelector('[data-description-diff]');
    if (!container) return;
    const suggestion = row.dataset.aiValue || '';
    container.textContent = suggestion;
  };

  const setAiDisplay = (display, label) => {
    if (!display) return;
    const empty = label === null || label === undefined || String(label).trim() === '';
    display.textContent = empty ? 'Not provided' : label;
  };

  // Dialog helpers
  root.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.openDialog);
      if (dialog) {
        if (button.dataset.duplicateCandidateId) {
          const candidateSelect = dialog.querySelector('#duplicate-candidate');
          if (candidateSelect) candidateSelect.value = button.dataset.duplicateCandidateId;
        }
        dialog.showModal();
        dialog.querySelector('textarea, select, input')?.focus();
      }
    });
  });
  root.querySelectorAll('dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-close-modal]').forEach((button) => {
      button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });

  // Kebab dropdown
  const closeAllKebabs = (except) => {
    document.querySelectorAll('.kebab.open').forEach((kebab) => {
      if (kebab === except) return;
      kebab.classList.remove('open');
      const menu = kebab.querySelector('.kebab-menu');
      if (menu) menu.hidden = true;
      const toggle = kebab.querySelector('[data-kebab-toggle]');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    });
  };
  document.querySelectorAll('[data-kebab-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const kebab = toggle.closest('.kebab');
      if (!kebab) return;
      const menu = kebab.querySelector('.kebab-menu');
      const isOpen = kebab.classList.contains('open');
      closeAllKebabs(kebab);
      kebab.classList.toggle('open', !isOpen);
      if (menu) menu.hidden = isOpen;
      toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    });
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.kebab')) closeAllKebabs();
  });

  // Proof input previews
  root.querySelectorAll('[data-proof-input]').forEach((proofInput) => {
    const fieldScope = proofInput.closest('.review-form-field') || root;
    const preview = fieldScope.querySelector('[data-proof-preview]');
    const errorList = fieldScope.querySelector('[data-proof-errors]');
    const maxFiles = Number(proofInput.dataset.maxFiles || 5);
    const maxSizeKb = Number(proofInput.dataset.maxSizeKb || 5120);
    const acceptTypes = (proofInput.accept || '').split(',').map((t) => t.trim()).filter(Boolean);
    const acceptExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    let pendingFiles = [];

    const syncInput = () => {
      const transfer = new DataTransfer();
      pendingFiles.forEach((f) => transfer.items.add(f));
      proofInput.files = transfer.files;
    };
    const renderPending = () => {
      if (!preview) return;
      preview.replaceChildren();
      pendingFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'review-proof-item';
        const image = document.createElement('img');
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        item.append(image);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'review-proof-delete';
        remove.setAttribute('aria-label', `Remove ${file.name}`);
        remove.textContent = '\u00d7';
        remove.addEventListener('click', () => {
          URL.revokeObjectURL(image.src);
          pendingFiles.splice(index, 1);
          syncInput();
          renderPending();
        });
        item.append(remove);
        preview.append(item);
      });
    };

    proofInput.addEventListener('change', () => {
      const errors = [];
      const accepted = [];
      [...(proofInput.files || [])].forEach((file) => {
        const ext = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
        const typeOk = (file.type && acceptTypes.includes(file.type)) || acceptExtensions.includes(ext);
        if (!typeOk) { errors.push(`${file.name} is not a JPG, PNG, or WebP image.`); return; }
        if (file.size > maxSizeKb * 1024) { errors.push(`${file.name} is larger than ${Math.round(maxSizeKb / 1024)} MB.`); return; }
        accepted.push(file);
      });
      const room = Math.max(0, maxFiles - pendingFiles.length);
      if (accepted.length > room) errors.push(`Up to ${maxFiles} photos can be uploaded at a time.`);
      pendingFiles = pendingFiles.concat(accepted.slice(0, room));
      if (errorList) {
        errorList.replaceChildren(...errors.map((m) => { const li = document.createElement('li'); li.textContent = m; return li; }));
        errorList.hidden = errors.length === 0;
      }
      syncInput();
      renderPending();
    });
  });

  // Update type template sync
  const updateTypeSelect = root.querySelector('[data-update-type-select]');
  const updateMessageTextarea = root.querySelector('[data-update-message]');
  const updateMessageOptional = root.querySelector('[data-update-message-optional]');
  if (updateTypeSelect && updateMessageTextarea) {
    let lastPrefilledTemplate = updateTypeSelect.selectedOptions[0]?.dataset.template || '';
    updateTypeSelect.addEventListener('change', () => {
      const selectedOption = updateTypeSelect.selectedOptions[0];
      if (!selectedOption) return;
      const newTemplate = selectedOption.dataset.template || '';
      const isRequired = selectedOption.dataset.messageRequired === '1';
      if (updateMessageTextarea.value.trim() === '' || updateMessageTextarea.value === lastPrefilledTemplate) {
        updateMessageTextarea.value = newTemplate;
      }
      lastPrefilledTemplate = newTemplate;
      updateMessageTextarea.required = isRequired;
      if (updateMessageOptional) updateMessageOptional.textContent = isRequired ? '(required)' : '(optional)';
    });
  }

  const toggleResolutionBtn = root.querySelector('[data-toggle-resolution]');
  const resolutionPanel = root.querySelector('[data-resolution-panel]');
  if (toggleResolutionBtn && resolutionPanel) {
    toggleResolutionBtn.addEventListener('click', () => {
      const willOpen = resolutionPanel.hidden;
      resolutionPanel.hidden = !willOpen;
      toggleResolutionBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      if (willOpen) resolutionPanel.querySelector('textarea')?.focus();
    });
  }

  // Review Gate & Mode Logic
  const modeBanner = root.querySelector('[data-mode-banner]');
  const modeBadge = root.querySelector('[data-mode-badge]');
  const modeNote = root.querySelector('[data-mode-note]');
  const authorityModeInput = root.querySelector('[data-authority-mode-input]');
  const jurisdictionOverrideInput = root.querySelector('[data-jurisdiction-override-input]');
  const agencyHiddenInput = root.querySelector('[data-agency-hidden-input]');
  const blockWithin = root.querySelector('[data-mode-block="within"]');
  const blockBeyond = root.querySelector('[data-mode-block="beyond"]');
  const officeSelect = root.querySelector('[data-office-select]');
  const agencySelect = root.querySelector('[data-agency-select]');
  const letterTextarea = root.querySelector('[data-referral-letter]');
  const residentMessageTextarea = root.querySelector('[data-resident-message]');
  const letterTemplatesElement = root.querySelector('[data-letter-templates]');
  let letterTemplates = {};
  try { if (letterTemplatesElement) letterTemplates = JSON.parse(letterTemplatesElement.textContent); } catch {}
  let currentMode = authorityModeInput?.value === 'beyond' ? 'beyond' : 'within';
  let modeOverridden = false;

  const updateAcceptAllVisibility = () => {
    if (!acceptAllButton) return;
    const aiRows = rows.filter((r) => r.dataset.aiPresent === 'true' && r.dataset.reviewRequired === 'true');
    acceptAllButton.hidden = aiRows.length === 0 || aiRows.every((r) => isRowResolved(r));
  };

  const updateGate = () => {
    const requiredRows = rows.filter((r) => r.dataset.reviewRequired === 'true');
    const reviewedCount = requiredRows.filter((r) => r.dataset.reviewed === 'true').length;
    const remaining = requiredRows.length - reviewedCount;
    if (countLabel) countLabel.textContent = `${reviewedCount} of ${requiredRows.length} reviewed`;
    updateAcceptAllVisibility();

    const category = root.querySelector('[name="category_id"]');
    const urgency = root.querySelector('[name="urgency"]');
    const missingCategory = !category?.value;
    const missingUrgency = !urgency?.value;

    const categoryGateHint = root.querySelector('[data-gate-hint="category"]');
    if (categoryGateHint) {
      categoryGateHint.className = `review-gate-field-hint ${missingCategory ? 'is-missing' : 'is-ready'}`;
      categoryGateHint.innerHTML = `<i class="fa-solid ${missingCategory ? 'fa-lock' : 'fa-check'}" aria-hidden="true"></i> <span>${missingCategory ? 'Required to route' : 'Ready to route'}</span>`;
    }
    const urgencyGateHint = root.querySelector('[data-gate-hint="urgency"]');
    if (urgencyGateHint) {
      urgencyGateHint.className = `review-gate-field-hint ${missingUrgency ? 'is-missing' : 'is-ready'}`;
      urgencyGateHint.innerHTML = `<i class="fa-solid ${missingUrgency ? 'fa-lock' : 'fa-check'}" aria-hidden="true"></i> <span>${missingUrgency ? 'Required to route' : 'Ready to route'}</span>`;
    }

    const hasUnverifiedAudio = root.dataset.hasUnverifiedAudio === 'true';
    const reasons = [];
    if (hasUnverifiedAudio) reasons.push('Audio verification required before routing.');
    if (remaining > 0) reasons.push(`Review ${remaining} more field${remaining === 1 ? '' : 's'}.`);
    if (missingCategory || missingUrgency) {
      reasons.push(`Select ${missingCategory && missingUrgency ? 'Category and Urgency' : (missingCategory ? 'Category' : 'Urgency')}.`);
    }

    if (currentMode === 'beyond') {
      if (routeButton) routeButton.disabled = true;
      const missingAgency = !agencySelect?.value;
      if (referralSubmit) referralSubmit.disabled = hasUnverifiedAudio || missingCategory || missingUrgency || missingAgency;
      if (missingAgency) reasons.push('Choose an agency.');
    } else {
      if (referralSubmit) referralSubmit.disabled = true;
      const missingOffice = Boolean(routeButton && !officeSelect?.value);
      if (missingOffice) reasons.push('Choose an office.');
      if (routeButton) routeButton.disabled = hasUnverifiedAudio || missingCategory || missingUrgency || missingOffice;
    }

    if (gateReason) {
      if (reasons.length > 0) {
        gateReason.innerHTML = `<i class="fa-solid fa-circle-exclamation text-amber-500 mr-1" aria-hidden="true"></i> <span>${reasons.join(' ')}</span>`;
        gateReason.className = 'review-gate-hint text-xs font-medium text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 inline-flex items-center';
      } else {
        gateReason.innerHTML = '<i class="fa-solid fa-check text-emerald-600 mr-1" aria-hidden="true"></i> <span>Ready to route</span>';
        gateReason.className = 'review-gate-hint text-xs font-medium text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 inline-flex items-center';
      }
    }

    const card2GateReason = root.querySelector('[data-gate-reason-card2]');
    if (card2GateReason) {
      if (root.dataset.isAfterRoute === 'true') {
        card2GateReason.hidden = true;
      } else if (hasUnverifiedAudio) {
        card2GateReason.textContent = 'Route gate: Audio verification required before routing';
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary is-missing';
        card2GateReason.hidden = false;
      } else if (missingCategory || missingUrgency) {
        card2GateReason.textContent = `Route gate: Select ${missingCategory && missingUrgency ? 'Category & Urgency' : (missingCategory ? 'Category' : 'Urgency')} to route`;
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary is-missing';
        card2GateReason.hidden = false;
      } else if (remaining > 0) {
        card2GateReason.textContent = `Route gate: ${remaining} field${remaining === 1 ? '' : 's'} pending review`;
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary';
        card2GateReason.hidden = false;
      } else {
        card2GateReason.textContent = 'Route gate: Review requirements met';
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary is-ready';
        card2GateReason.hidden = false;
      }
    }
  };

  // Step navigation listeners
  const attemptStep2 = () => {
    const category = root.querySelector('[name="category_id"]');
    const urgency = root.querySelector('[name="urgency"]');
    const hasUnverifiedAudio = root.dataset.hasUnverifiedAudio === 'true';
    if (hasUnverifiedAudio || !category?.value || !urgency?.value) {
      const card2 = root.querySelector('[data-gate-reason-card2]');
      if (card2) {
        card2.classList.remove('rw-shake');
        void card2.offsetWidth;
        card2.classList.add('rw-shake');
      }
      if (!category?.value) category?.focus();
      else if (!urgency?.value) root.querySelector('[data-urgency-pill-btn]')?.focus();
      return false;
    }
    setWorkspaceStep(2);
    return true;
  };

  root.querySelector('#rw-btn-to-step2')?.addEventListener('click', attemptStep2);
  root.querySelector('#rw-btn-back-step1')?.addEventListener('click', () => setWorkspaceStep(1));
  root.querySelectorAll('[data-workspace-step]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = Number(btn.dataset.workspaceStep);
      if (target === 2 && root.dataset.isAfterRoute !== 'true') {
        attemptStep2();
        return;
      }
      setWorkspaceStep(target);
    });
  });

  rows.forEach((row) => {
    updateRow(row);
    renderDescriptionDiff(row);
    const control = row.querySelector('[data-final-control]');
    if (control) {
      const markEdited = () => {
        row.dataset.finalTouched = 'true';
        snapshotUndo(row);
        setFinalState(row, 'Edited');
        if (row.dataset.field === 'urgency') syncUrgencyPills();
        updateGate();
      };
      control.addEventListener('input', markEdited);
      control.addEventListener('change', markEdited);
    }

    row.querySelector('[data-accept-row]')?.addEventListener('click', () => {
      snapshotUndo(row);
      if (control) {
        control.value = row.dataset.aiValue || '';
        control.dispatchEvent(new Event('change', { bubbles: true }));
        setFinalState(row, 'Accepted');
        row.dataset.finalTouched = 'false';
        if (row.dataset.field === 'urgency') syncUrgencyPills();
      }
      updateGate();
    });
    row.querySelector('[data-use-ai]')?.addEventListener('click', () => {
      if (!control || !row.dataset.aiValue) return;
      snapshotUndo(row);
      control.value = row.dataset.aiValue;
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Accepted');
      row.dataset.finalTouched = 'false';
      if (row.dataset.field === 'urgency') syncUrgencyPills();
      updateGate();
    });
    row.querySelector('[data-use-resident]')?.addEventListener('click', () => {
      if (!control) return;
      snapshotUndo(row);
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-use-original]')?.addEventListener('click', () => {
      if (!control) return;
      snapshotUndo(row);
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('input', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-undo-row]')?.addEventListener('click', () => {
      const state = row.dataset.undoState || 'AI suggested';
      if (control && row.dataset.undoValue !== undefined) {
        control.value = row.dataset.undoValue;
        control.dispatchEvent(new Event('change', { bubbles: true }));
      }
      const badge = row.querySelector('[data-final-state]');
      if (badge) {
        badge.textContent = state;
        badge.className = `review-badge ${stateClass(state)}`;
      }
      row.dataset.reviewed = state === 'AI suggested' ? 'false' : 'true';
      if (row.dataset.field === 'urgency') syncUrgencyPills();
      setResolvedUi(row);
      updateGate();
    });
  });

  acceptAllButton?.addEventListener('click', () => {
    rows.forEach((row) => {
      if (row.dataset.aiPresent !== 'true' || !row.dataset.aiValue) return;
      snapshotUndo(row);
      const control = row.querySelector('[data-final-control]');
      if (!control) return;
      control.value = row.dataset.aiValue;
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Accepted');
      row.dataset.finalTouched = 'false';
    });
    syncUrgencyPills();
    updateGate();
  });

  const routingMapElement = root.querySelector('[data-routing-map]');
  const routingReason = root.querySelector('[data-routing-reason]');
  let routingMap = {};
  try { if (routingMapElement) routingMap = JSON.parse(routingMapElement.textContent); } catch {}
  let currentRuleText = modeNote?.querySelector('[data-matched-rule-text]')?.textContent.trim() || 'Category default';

  const updateStickySummary = () => {
    const summaryTextEl = root.querySelector('[data-action-summary-text]');
    if (!summaryTextEl) return;
    const urgency = root.querySelector('[name="urgency"]')?.value || 'not set';
    if (currentMode === 'within') {
      const officeName = officeSelect?.selectedOptions[0]?.text || 'selected office';
      summaryTextEl.textContent = `Route to: ${officeName} · ${urgency}`;
    } else {
      const agencyName = agencySelect?.selectedOptions[0]?.text || 'external agency';
      summaryTextEl.textContent = `Refer to: ${agencyName} · ${urgency}`;
    }
  };

  const applyLetterTemplate = (officeId) => {
    const template = letterTemplates[officeId];
    if (!template) return;
    if (letterTextarea && letterTextarea.dataset.touched !== 'true') letterTextarea.value = template.letter || '';
    if (residentMessageTextarea && residentMessageTextarea.dataset.touched !== 'true') residentMessageTextarea.value = template.resident_message || '';
    updateStickySummary();
  };

  const setMode = (mode, { fromUser = false } = {}) => {
    currentMode = mode === 'beyond' ? 'beyond' : 'within';
    if (fromUser) modeOverridden = true;
    if (authorityModeInput) authorityModeInput.value = currentMode;
    const ruleDefaultMode = modeBanner?.dataset.ruleMode || 'within';
    if (jurisdictionOverrideInput) {
      jurisdictionOverrideInput.value = (fromUser && currentMode !== ruleDefaultMode) ? '1' : '0';
    }
    const within = currentMode === 'within';

    root.querySelectorAll('.mode-seg-btn').forEach((btn) => {
      const isTarget = btn.dataset.modeBtn === currentMode;
      btn.className = `mode-seg-btn px-3 py-1.5 rounded-md font-bold transition ${isTarget ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'}`;
    });
    if (modeBanner) {
      modeBanner.className = `review-mode-banner rounded-xl border border-l-[3px] p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 transition-all ${within ? 'border-l-emerald-600 bg-emerald-50/60 border-emerald-200' : 'border-l-indigo-600 bg-indigo-50/60 border-indigo-200'}`;
    }
    if (modeBadge) modeBadge.textContent = within ? 'Within barangay authority' : 'Beyond barangay authority';
    if (modeNote) {
      modeNote.innerHTML = within
        ? `Matched rule: <span data-matched-rule-text>${currentRuleText}</span>. Assign it to an office and staff member.`
        : 'External referral required. A referral letter is prepared and 3-day follow-up is tracked.';
    }
    if (blockWithin) blockWithin.hidden = !within;
    if (blockBeyond) blockBeyond.hidden = within;
    if (officeSelect) officeSelect.disabled = !within;
    if (agencySelect) {
      agencySelect.disabled = within;
      if (!within && !agencySelect.value && agencySelect.options.length > 1) agencySelect.selectedIndex = 1;
      if (agencyHiddenInput) agencyHiddenInput.value = agencySelect.value || '';
    }
    const followUpOwnerSelect = root.querySelector('#follow_up_owner_id');
    if (followUpOwnerSelect) followUpOwnerSelect.disabled = within;
    if (letterTextarea) letterTextarea.disabled = within;
    if (residentMessageTextarea) residentMessageTextarea.disabled = within;
    if (routeButton) routeButton.style.display = within ? '' : 'none';
    if (referralSubmit) referralSubmit.style.display = within ? 'none' : '';
    if (!within && agencySelect) applyLetterTemplate(agencySelect.value);
    updateStickySummary();
    updateGate();
  };

  const assignedSelect = root.querySelector('[data-assigned-select]');
  let assignedTouched = false;
  assignedSelect?.addEventListener('change', () => {
    assignedTouched = true;
    const note = root.querySelector('[data-unassigned-note]');
    if (note) note.style.display = assignedSelect.value === '' ? 'flex' : 'none';
    updateStickySummary();
  });

  root.querySelectorAll('.mode-seg-btn').forEach((btn) => {
    btn.addEventListener('click', () => setMode(btn.dataset.modeBtn, { fromUser: true }));
  });
  agencySelect?.addEventListener('change', () => {
    agencySelect.dataset.touched = 'true';
    if (agencyHiddenInput) agencyHiddenInput.value = agencySelect.value || '';
    applyLetterTemplate(agencySelect.value);
    updateGate();
  });
  officeSelect?.addEventListener('change', () => {
    officeTouched = true;
    updateStickySummary();
    updateGate();
  });
  root.querySelectorAll('.btn-quick-fill').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetArea = root.querySelector('#' + btn.dataset.chipTarget);
      if (targetArea && btn.dataset.chipText) {
        targetArea.value = btn.dataset.chipText;
        targetArea.dispatchEvent(new Event('input', { bubbles: true }));
        targetArea.focus();
      }
    });
  });

  root.querySelector('[name="urgency"]')?.addEventListener('change', (e) => {
    const label = root.querySelector('[data-urgency-label]');
    if (label) label.textContent = e.target.value || 'not set';
    updateStickySummary();
  });

  root.querySelector('[name="category_id"]')?.addEventListener('change', (event) => {
    const decision = routingMap[event.target.value];
    if (!decision) return;
    const ruleMode = decision.within_authority ? 'within' : 'beyond';
    currentRuleText = decision.reason || `${decision.office_name || 'No office'}`;
    if (routingReason) routingReason.textContent = `Rule: ${decision.office_name || 'No office'} — ${decision.reason}`;
    if (decision.within_authority && officeSelect && !officeTouched && decision.office_id) {
      officeSelect.value = String(decision.office_id);
    } else if (!decision.within_authority && agencySelect && agencySelect.dataset.touched !== 'true' && decision.office_id) {
      agencySelect.value = String(decision.office_id);
      if (agencyHiddenInput) agencyHiddenInput.value = agencySelect.value;
      applyLetterTemplate(agencySelect.value);
    }
    if (!modeOverridden) setMode(ruleMode);
    updateStickySummary();
    updateGate();
  });

  // AI Polling (stops after 24 attempts; never overwrites manual edits)
  const applyAiResult = (result) => {
    aiState = result.status;
    root.dataset.aiState = aiState;
    if (result.status === 'ready') {
      if (aiStateBadge) {
        aiStateBadge.className = 'inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-xs font-bold';
        aiStateBadge.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles text-[10px]" aria-hidden="true"></i> AI ready';
      }
      const confidence = Number(result.confidence);
      if (confidenceWrap) confidenceWrap.hidden = false;
      if (confidenceBadge) confidenceBadge.textContent = `Confidence ${Number.isFinite(confidence) ? Math.round(confidence * 100) : '—'}%`;
      if (stateMessage) stateMessage.textContent = 'AI suggestions are ready. Your final values were not changed; choose Use AI or Accept for each suggestion.';
      if (acceptAllButton) { acceptAllButton.hidden = false; acceptAllButton.disabled = false; }
      rows.forEach((row) => {
        const value = result.values[row.dataset.field] || { value: null, label: null };
        row.dataset.aiValue = value.value ?? '';
        row.dataset.aiLabel = value.label ?? '';
        row.dataset.aiPresent = value.value !== null && value.value !== '' ? 'true' : 'false';
        row.dataset.reviewRequired = row.dataset.aiPresent;
        const display = row.querySelector('[data-ai-value-display]');
        if (display && row.dataset.field !== 'description') setAiDisplay(display, value.label);
        renderDescriptionDiff(row);
        if (row.dataset.finalTouched !== 'true') {
          setFinalState(row, row.dataset.aiPresent === 'true' ? 'AI suggested' : 'Resident');
          row.dataset.reviewed = row.dataset.aiPresent === 'true' ? 'false' : 'true';
        }
        updateRow(row);
      });
      updateGate();
      return;
    }
    if (result.status === 'unavailable') {
      if (aiStateBadge) aiStateBadge.textContent = 'AI unavailable';
      if (stateMessage) stateMessage.textContent = 'AI suggestions are not available. Confirm Category and Urgency manually.';
      if (acceptAllButton) acceptAllButton.hidden = true;
      rows.forEach(updateRow);
      updateGate();
    }
  };

  let aiPollController = null;
  let aiPollTimer = null;

  root.querySelector('[data-stop-ai]')?.addEventListener('click', () => {
    aiState = 'unavailable';
    if (aiPollTimer) {
      window.clearTimeout(aiPollTimer);
      aiPollTimer = null;
    }
    if (aiPollController) {
      aiPollController.abort();
      aiPollController = null;
    }
  });

  const pollAi = async () => {
    if (aiState !== 'pending' || pollAttempts >= 24) return;
    pollAttempts += 1;
    aiPollController = new AbortController();
    try {
      const response = await fetch(pollUrl, {
        headers: { Accept: 'application/json' },
        signal: aiPollController.signal,
      });
      if (!response.ok) throw new Error(`AI status request failed (${response.status}).`);
      const result = await response.json();
      if (result.status === 'pending') {
        aiPollTimer = window.setTimeout(pollAi, 5000);
        return;
      }
      applyAiResult(result);
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      aiPollTimer = window.setTimeout(pollAi, 5000);
    }
  };

  form?.addEventListener('submit', (event) => {
    if (event.submitter?.value !== 'route' && event.submitter?.value !== 'referral') return;
    rows.forEach((row) => { row.dataset.reviewed = 'true'; });
    updateGate();
    const submitterDisabled = event.submitter.value === 'route' ? routeButton?.disabled : referralSubmit?.disabled;
    if (submitterDisabled) event.preventDefault();
  });

  setWorkspaceStep(currentWorkspaceStep);
  syncUrgencyPills();
  setMode(currentMode);
  updateStickySummary();
  updateGate();
  if (aiState === 'pending') window.setTimeout(pollAi, 1500);
})();
</script>
@endpush