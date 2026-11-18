{{-- Shared "Ask Reporter for More Info" chat modal.
    Triggers: any button with [data-chat-open][data-report-id] carrying the
    report's tracking code / category / status via data attributes.
    Talks to admin.reports.info-requests.index (GET) and .store (POST, JSON). --}}
<dialog id="info-chat-modal" class="chat-modal" role="dialog" aria-modal="true" aria-labelledby="chat-modal-title">
  <div class="chat-modal-frame">
    <header class="chat-modal-header">
      <div class="chat-modal-heading">
        <h2 id="chat-modal-title" class="chat-modal-tracking font-mono"></h2>
        <div class="chat-modal-meta">
          <span class="chat-modal-category" data-chat-category></span>
          <span class="status-badge b-gold" data-chat-status></span>
        </div>
      </div>
      <button type="button" class="review-modal-close chat-modal-close" data-chat-close aria-label="Close conversation">&times;</button>
    </header>

    <details class="chat-modal-summary">
      <summary>Report summary</summary>
      <p class="chat-modal-summary-text" data-chat-summary></p>
    </details>

    <div class="chat-modal-log" data-chat-log tabindex="0" aria-live="polite">
      <p class="chat-modal-empty" data-chat-empty hidden>No messages yet. Ask the reporter what you need to know.</p>
      {{-- Bubbles, date dividers and the status line are rendered by the script. --}}
    </div>

    <form class="chat-modal-composer" data-chat-form>
      <div class="chat-modal-chips" aria-label="Quick replies">
        <button type="button" class="chat-chip" data-chat-chip>Please send a clearer photo</button>
        <button type="button" class="chat-chip" data-chat-chip>What is the exact location?</button>
        <button type="button" class="chat-chip" data-chat-chip>Is anyone in danger right now?</button>
      </div>
      <p class="chat-modal-error" data-chat-error hidden></p>
      <div class="chat-modal-composer-row">
        <label class="sr-only" for="chat-input">Message to the reporter</label>
        <textarea id="chat-input" data-chat-input rows="1" maxlength="2000"
          placeholder="Ask the reporter what you need to know&hellip;"></textarea>
        <button type="submit" class="btn btn-navy btn-small" data-chat-send disabled>Send</button>
      </div>
    </form>
  </div>
</dialog>

<script nonce>
(function () {
  'use strict';

  const modal = document.getElementById('info-chat-modal');
  if (!modal) return;

  const log = modal.querySelector('[data-chat-log]');
  const emptyState = modal.querySelector('[data-chat-empty]');
  const input = modal.querySelector('[data-chat-input]');
  const sendButton = modal.querySelector('[data-chat-send]');
  const errorLine = modal.querySelector('[data-chat-error]');
  const form = modal.querySelector('[data-chat-form]');
  const statusBadge = modal.querySelector('[data-chat-status]');
  const categoryLabel = modal.querySelector('[data-chat-category]');
  const summaryText = modal.querySelector('[data-chat-summary]');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  let reportId = null;
  let lastThreadAt = null; // ISO time of the newest message, for polling.
  let pollTimer = null;
  let inFlight = false;
  let lastFocused = null;

  const formatTime = (iso) => {
    const date = new Date(iso);
    return date.toLocaleString(undefined, {
      month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
    });
  };

  const formatDate = (iso) => new Date(iso).toLocaleDateString(undefined, {
    month: 'short', day: 'numeric', year: 'numeric',
  });

  const scrollToBottom = () => {
    log.scrollTop = log.scrollHeight;
  };

  const clearLog = () => {
    log.querySelectorAll('.chat-bubble-row, .chat-date-divider, .chat-status-line').forEach((node) => node.remove());
  };

  const appendMessage = (message, lastDate) => {
    const messageDate = formatDate(message.at);
    if (messageDate !== lastDate.value) {
      const divider = document.createElement('li');
      divider.className = 'chat-date-divider';
      divider.innerHTML = '<span></span><time datetime="' + message.at + '">' + messageDate + '</time><span></span>';
      log.insertBefore(divider, emptyState);
      lastDate.value = messageDate;
    }

    const row = document.createElement('li');
    row.className = 'chat-bubble-row chat-bubble-' + message.sender;
    row.innerHTML =
      '<div class="chat-bubble">' +
        '<p class="chat-bubble-text"></p>' +
        '<span class="chat-bubble-meta"></span>' +
      '</div>';
    row.querySelector('.chat-bubble-text').textContent = message.text;
    row.querySelector('.chat-bubble-meta').textContent =
      message.sender_name + ' · ' + formatTime(message.at);
    log.insertBefore(row, emptyState);
  };

  const appendStatusLine = (messages) => {
    const last = messages[messages.length - 1];
    if (!last || last.sender !== 'staff') return;
    const line = document.createElement('li');
    line.className = 'chat-status-line';
    line.textContent = "Waiting for the resident's reply\u2026";
    log.insertBefore(line, emptyState);
  };

  const renderThread = (data) => {
    clearLog();
    const lastDate = { value: null };
    (data.messages || []).forEach((message) => appendMessage(message, lastDate));
    appendStatusLine(data.messages || []);
    emptyState.hidden = (data.messages || []).length > 0;
    if (data.messages.length) lastThreadAt = data.messages[data.messages.length - 1].at;
    scrollToBottom();
  };

  const renderSummary = (report, summary) => {
    statusBadge.textContent = report.status_label;
    categoryLabel.textContent = report.category || 'Category not assigned';
    summaryText.textContent =
      (report.description || '') + (report.purok ? ' — ' + report.purok : '');
  };

  const fetchThread = async (markSeen) => {
    if (!reportId) return;
    const url = '/admin/reports/' + reportId + '/info-requests' + (markSeen ? '?seen=1' : '');
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('Could not load the conversation.');
    return response.json();
  };

  const schedulePoll = () => {
    window.clearTimeout(pollTimer);
    // Poll only while the modal is open and the resident has not replied yet.
    if (!modal.open || modal.querySelector('.chat-status-line') === null) return;
    pollTimer = window.setTimeout(async () => {
      try {
        const data = await fetchThread(false);
        renderThread(data);
      } catch {
        // Polling is best-effort; the next cycle retries.
      }
      schedulePoll();
    }, 12000);
  };

  const updateSendState = () => {
    sendButton.disabled = inFlight || input.value.trim() === '';
  };

  const autoGrow = () => {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 140) + 'px';
  };

  const confirmUnsent = () => input.value.trim() !== ''
    ? window.confirm('You have an unsent message. Close anyway?')
    : true;

  const close = () => {
    if (!confirmUnsent()) return;
    modal.close();
  };

  const open = async (trigger) => {
    lastFocused = trigger;
    reportId = trigger.dataset.reportId;
    statusBadge.textContent = trigger.dataset.chatStatus || '';
    categoryLabel.textContent = trigger.dataset.chatCategory || '';
    summaryText.textContent = trigger.dataset.chatSummary || '';
    input.value = '';
    updateSendState();
    errorLine.hidden = true;

    modal.showModal();
    document.body.classList.add('chat-modal-open');
    log.replaceChildren(emptyState);
    emptyState.hidden = false;

    // Immediately clear red circle notification badge since thread is opened and marked seen
    const notifBadge = trigger.querySelector('[data-chat-notif-circle]') || document.querySelector('[data-chat-notif-circle]');
    if (notifBadge) notifBadge.style.display = 'none';

    try {
      const data = await fetchThread(true);
      renderThread(data);
      schedulePoll();
    } catch (error) {
      errorLine.textContent = error.message;
      errorLine.hidden = false;
    }
    input.focus();
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-chat-open]');
    if (trigger) {
      event.preventDefault();
      open(trigger);
      return;
    }
    if (event.target.closest('[data-chat-close]')) {
      close();
    }
  });

  // Clicking the backdrop closes (with the unsent-text warning when needed).
  modal.addEventListener('click', (event) => {
    if (event.target === modal) close();
  });

  modal.addEventListener('close', () => {
    document.body.classList.remove('chat-modal-open');
    window.clearTimeout(pollTimer);
    input.value = '';
    updateSendState();
    if (lastFocused) {
      lastFocused.focus();
      lastFocused = null;
    }
  });

  // Focus trap while the modal is open.
  modal.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = [...modal.querySelectorAll('button, textarea, [href], [tabindex]:not([tabindex="-1"])')]
      .filter((el) => !el.disabled && el.offsetParent !== null);
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  modal.querySelectorAll('[data-chat-chip]').forEach((chip) => {
    chip.addEventListener('click', () => {
      const text = chip.textContent.trim();
      input.value = input.value.trim() === '' ? text : input.value.trim() + ' ' + text;
      autoGrow();
      updateSendState();
      input.focus();
    });
  });

  input.addEventListener('input', () => {
    autoGrow();
    updateSendState();
  });

  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const question = input.value.trim();
    if (question === '' || inFlight || !reportId) return;

    inFlight = true;
    updateSendState();
    errorLine.hidden = true;

    try {
      const response = await fetch('/admin/reports/' + reportId + '/info-requests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ question }),
      });

      if (response.status === 422) {
        const payload = await response.json();
        const messages = payload.errors ? Object.values(payload.errors).flat() : ['The message could not be sent.'];
        errorLine.textContent = messages.join(' ');
        errorLine.hidden = false;
        return;
      }
      if (!response.ok) throw new Error('The message could not be sent. Please try again.');

      const data = await response.json();
      input.value = '';
      autoGrow();
      renderThread(data);
      schedulePoll();
      // Update any on-page summary strip for this report.
      const strip = document.querySelector('[data-chat-summary-strip][data-report-id="' + reportId + '"]');
      if (strip) {
        const count = strip.querySelector('[data-chat-message-count]');
        const awaiting = strip.querySelector('[data-chat-awaiting]');
        if (count) count.textContent = data.summary.count + ' messages sent';
        if (awaiting) awaiting.hidden = !data.summary.awaiting_reply;
      }
    } catch (error) {
      errorLine.textContent = error.message;
      errorLine.hidden = false;
    } finally {
      inFlight = false;
      updateSendState();
      input.focus();
    }
  });
})();
</script>
