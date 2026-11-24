@props([
    'targetInput' => 'description',
    'origin' => 'resident',
    'maxSeconds' => (int) config('voice.max_seconds', 120),
    'analyzeUrl' => null,
    'consentCheckboxId' => null,
])

@php
    $demoSampleEnabled = (bool) config('voice.demo_sample_enabled', false) || app()->environment('local', 'testing');
    $recorderId = 'voice_rec_' . \Illuminate\Support\Str::random(8);
@endphp

<div id="{{ $recorderId }}" class="voice-recorder-wrapper" style="margin-bottom:1rem;"
     data-target-input="{{ $targetInput }}"
     data-transcribe-url="{{ route('reports.voice.transcribe') }}"
     data-analyze-url="{{ $analyzeUrl }}"
     data-consent-checkbox-id="{{ $consentCheckboxId }}"
     data-csrf="{{ csrf_token() }}"
     data-max-seconds="{{ $maxSeconds }}">

  {{-- Hidden input for form submission --}}
  <input type="hidden" name="audio_token" id="{{ $recorderId }}_token" value="">
  <input type="hidden" name="voice_consent_at" id="{{ $recorderId }}_consent_at" value="">

  @if($origin === 'resident')
    <div class="voice-consent-line" style="display:flex;align-items:center;gap:0.4rem;font-size:0.82rem;color:#475569;margin-bottom:0.5rem;">
      <i class="fa-solid fa-shield-halved" style="color:var(--blue);"></i>
      <span>My recording is processed by AI and seen by barangay staff only</span>
    </div>
  @endif

  {{-- Top Action / Idle Bar --}}
  <div class="voice-idle-state" style="display:flex;align-items:center;flex-wrap:wrap;gap:0.75rem;">
    <button type="button" class="voice-btn-record"
            style="min-height:44px;display:inline-flex;align-items:center;gap:0.5rem;padding:0.55rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;cursor:pointer;transition:all 0.15s ease;">
      <i class="fa-solid fa-microphone" style="color:#2563eb;"></i>
      <span>{{ $origin === 'resident' ? 'I agree, start recording' : 'Record voice note' }}</span>
      <span style="font-size:0.75rem;opacity:0.8;font-weight:400;">(max {{ $maxSeconds }}s)</span>
    </button>

    <label class="voice-btn-upload" style="min-height:44px;display:inline-flex;align-items:center;gap:0.4rem;padding:0.55rem 0.85rem;border-radius:0.5rem;font-size:0.85rem;font-weight:500;background:#f8fafc;color:#475569;border:1px solid #cbd5e1;cursor:pointer;">
      <i class="fa-solid fa-upload"></i>
      <span>Upload audio file</span>
      <input type="file" accept="audio/*" class="voice-file-input" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;">
    </label>

    @if($demoSampleEnabled)
      <button type="button" class="voice-btn-demo"
              style="min-height:44px;display:inline-flex;align-items:center;gap:0.35rem;padding:0.55rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;background:#f1f5f9;color:#64748b;border:1px dashed #94a3b8;cursor:pointer;">
        <i class="fa-solid fa-flask"></i> Load demo sample
      </button>
    @endif
  </div>

  @if($origin === 'resident')
    <p style="margin:0.35rem 0 0;font-size:0.75rem;color:#64748b;">
      <i class="fa-solid fa-circle-info"></i> Speak naturally in English, Filipino, Bisaya, or Chavacano. Typing is recommended if you wish to remain strictly anonymous.
    </p>
  @endif

  {{-- Recording Active State --}}
  <div class="voice-recording-state" style="display:none;margin-top:0.75rem;padding:0.85rem 1rem;border-radius:0.5rem;background:#fff1f2;border:1px solid #fecdd3;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <span class="voice-pulse-dot" style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#e11d48;animation:pulse 1s infinite;"></span>
      <span style="font-weight:700;color:#9f1239;font-size:0.9rem;">Recording...</span>
      <span class="voice-timer" style="font-family:monospace;font-size:0.9rem;font-weight:700;color:#881337;">00:00 / 01:00</span>
    </div>

    {{-- Live visual volume level meter --}}
    <div style="flex:1;max-width:180px;height:10px;background:#ffe4e6;border-radius:5px;overflow:hidden;display:flex;align-items:center;">
      <div class="voice-level-bar" style="width:0%;height:100%;background:#e11d48;transition:width 0.1s ease;"></div>
    </div>

    <div style="display:flex;align-items:center;gap:0.5rem;">
      <button type="button" class="voice-btn-stop" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.4rem 0.8rem;border-radius:0.375rem;background:#e11d48;color:#fff;border:none;font-size:0.85rem;font-weight:600;cursor:pointer;">
        <i class="fa-solid fa-stop"></i> Stop & Transcribe
      </button>
      <button type="button" class="voice-btn-cancel" style="padding:0.4rem 0.6rem;border-radius:0.375rem;background:transparent;color:#9f1239;border:1px solid #fda4af;font-size:0.85rem;cursor:pointer;">
        Cancel
      </button>
    </div>
  </div>

  {{-- Processing / Transcribing State --}}
  <div class="voice-processing-state" style="display:none;margin-top:0.75rem;padding:0.85rem 1rem;border-radius:0.5rem;background:#f8fafc;border:1px solid #e2e8f0;align-items:center;gap:0.75rem;">
    <i class="fa-solid fa-spinner fa-spin" style="color:#2563eb;font-size:1.1rem;"></i>
    <span style="font-size:0.875rem;color:#334155;font-weight:600;">Transcribing your voice note with AI...</span>
  </div>

  {{-- Transcribed / Ready State --}}
  <div class="voice-ready-state" style="display:none;margin-top:0.75rem;padding:0.85rem 1rem;border-radius:0.5rem;background:#f0fdf4;border:1px solid #bbf7d0;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
      <div style="display:flex;align-items:center;gap:0.5rem;">
        <i class="fa-solid fa-circle-check" style="color:#16a34a;font-size:1.1rem;"></i>
        <span style="font-weight:700;color:#166534;font-size:0.875rem;">Voice recording attached</span>
      </div>
      <button type="button" class="voice-btn-reset" style="padding:0.3rem 0.6rem;border-radius:0.375rem;background:#fff;border:1px solid #cbd5e1;font-size:0.75rem;color:#475569;cursor:pointer;">
        <i class="fa-solid fa-rotate-left"></i> Discard & record again
      </button>
    </div>

    {{-- Audio preview player --}}
    <audio class="voice-audio-preview" controls style="width:100%;height:36px;margin-top:0.6rem;outline:none;"></audio>

    {{-- Notice Card for Uncertain / Failed Transcriptions --}}
    <div class="voice-notice-card" style="display:none;margin-top:0.6rem;padding:0.75rem;border-radius:0.375rem;background:#fffbeb;border:1px solid #fef08a;color:#92400e;font-size:0.85rem;">
      <div style="display:flex;align-items:flex-start;gap:0.5rem;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-top:0.15rem;color:#d97706;shrink:0;"></i>
        <span class="voice-notice-text">
          We couldn't clearly transcribe parts of your voice note. Barangay staff will listen and review it before processing.
        </span>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const container = document.getElementById('{{ $recorderId }}');
  if (!container) return;

  const targetInputId = container.dataset.targetInput;
  const targetInput = document.getElementById(targetInputId);
  const transcribeUrl = container.dataset.transcribeUrl;
  const analyzeUrl = container.dataset.analyzeUrl;
  const endpointUrl = analyzeUrl || transcribeUrl;
  const consentCheckboxId = container.dataset.consentCheckboxId;
  const csrfToken = container.dataset.csrf;
  const maxSec = parseInt(container.dataset.maxSeconds, 10) || 120;

  const tokenInput = container.querySelector('#{{ $recorderId }}_token');
  const idleState = container.querySelector('.voice-idle-state');
  const recordingState = container.querySelector('.voice-recording-state');
  const processingState = container.querySelector('.voice-processing-state');
  const readyState = container.querySelector('.voice-ready-state');
  const btnRecord = container.querySelector('.voice-btn-record');
  const fileInput = container.querySelector('.voice-file-input');
  const uploadLabel = container.querySelector('.voice-btn-upload');
  const btnDemo = container.querySelector('.voice-btn-demo');
  const btnStop = container.querySelector('.voice-btn-stop');
  const btnCancel = container.querySelector('.voice-btn-cancel');
  const btnReset = container.querySelector('.voice-btn-reset');
  const timerEl = container.querySelector('.voice-timer');
  const levelBar = container.querySelector('.voice-level-bar');
  const audioPreview = container.querySelector('.voice-audio-preview');
  const noticeCard = container.querySelector('.voice-notice-card');
  const noticeText = container.querySelector('.voice-notice-text');

  // Consent checkbox integration
  if (consentCheckboxId) {
    const consentCb = document.getElementById(consentCheckboxId);
    function syncConsentState() {
      if (!consentCb) return;
      const isGranted = consentCb.checked;
      btnRecord.disabled = !isGranted;
      btnRecord.style.opacity = isGranted ? '1' : '0.5';
      btnRecord.style.cursor = isGranted ? 'pointer' : 'not-allowed';

      fileInput.disabled = !isGranted;
      if (uploadLabel) {
        uploadLabel.style.opacity = isGranted ? '1' : '0.5';
        uploadLabel.style.cursor = isGranted ? 'pointer' : 'not-allowed';
      }
      if (btnDemo) {
        btnDemo.disabled = !isGranted;
        btnDemo.style.opacity = isGranted ? '1' : '0.5';
        btnDemo.style.cursor = isGranted ? 'pointer' : 'not-allowed';
      }
    }
    if (consentCb) {
      consentCb.addEventListener('change', syncConsentState);
      syncConsentState();
    }
  }

  let mediaRecorder = null;
  let audioStream = null;
  let audioChunks = [];
  let timerInterval = null;
  let secondsElapsed = 0;
  let audioCtx = null;
  let analyser = null;
  let animFrame = null;

  function showState(state) {
    idleState.style.display = state === 'idle' ? 'flex' : 'none';
    recordingState.style.display = state === 'recording' ? 'flex' : 'none';
    processingState.style.display = state === 'processing' ? 'flex' : 'none';
    readyState.style.display = state === 'ready' ? 'block' : 'none';
  }

  function formatTime(sec) {
    const m = Math.floor(sec / 60).toString().padStart(2, '0');
    const s = (sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }

  function stopAudioTracks() {
    if (audioStream) {
      audioStream.getTracks().forEach(t => t.stop());
      audioStream = null;
    }
    if (animFrame) {
      cancelAnimationFrame(animFrame);
      animFrame = null;
    }
    if (audioCtx) {
      audioCtx.close().catch(() => {});
      audioCtx = null;
    }
    if (timerInterval) {
      clearInterval(timerInterval);
      timerInterval = null;
    }
  }

  // Ensure tracks released on page nav
  window.addEventListener('pagehide', stopAudioTracks);
  document.addEventListener('turbo:before-render', stopAudioTracks);

  async function startRecording() {
    try {
      audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (err) {
      alert('Microphone access was denied or is unavailable. Please allow microphone access or upload an audio file.');
      return;
    }

    const consentAtInput = container.querySelector('#{{ $recorderId }}_consent_at');
    if (consentAtInput) {
      consentAtInput.value = new Date().toISOString();
    }

    audioChunks = [];
    secondsElapsed = 0;
    timerEl.textContent = `00:00 / ${formatTime(maxSec)}`;
    levelBar.style.width = '0%';

    // Setup visual audio level
    try {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      analyser = audioCtx.createAnalyser();
      analyser.fftSize = 64;
      const sourceNode = audioCtx.createMediaStreamSource(audioStream);
      sourceNode.connect(analyser);

      const bufferLength = analyser.frequencyBinCount;
      const dataArray = new Uint8Array(bufferLength);

      function updateVolume() {
        if (!analyser) return;
        analyser.getByteFrequencyData(dataArray);
        let sum = 0;
        for (let i = 0; i < bufferLength; i++) {
          sum += dataArray[i];
        }
        const avg = sum / bufferLength;
        const pct = Math.min(100, Math.round((avg / 128) * 100));
        levelBar.style.width = `${pct}%`;
        animFrame = requestAnimationFrame(updateVolume);
      }
      updateVolume();
    } catch (e) {
      // Analyser fallback
    }

    const mimeTypes = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg'];
    let chosenMime = '';
    for (const m of mimeTypes) {
      if (MediaRecorder.isTypeSupported(m)) {
        chosenMime = m;
        break;
      }
    }

    mediaRecorder = chosenMime ? new MediaRecorder(audioStream, { mimeType: chosenMime }) : new MediaRecorder(audioStream);

    mediaRecorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) audioChunks.push(e.data);
    };

    mediaRecorder.onstop = () => {
      const recordedBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
      stopAudioTracks();
      uploadAndTranscribe(recordedBlob, secondsElapsed);
    };

    mediaRecorder.start(250);
    showState('recording');

    timerInterval = setInterval(() => {
      secondsElapsed++;
      timerEl.textContent = `${formatTime(secondsElapsed)} / ${formatTime(maxSec)}`;
      if (secondsElapsed >= maxSec) {
        stopRecording();
      }
    }, 1000);
  }

  function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
  }

  function cancelRecording() {
    stopAudioTracks();
    showState('idle');
  }

  async function uploadAndTranscribe(blob, durationSec, mockScenario = null) {
    showState('processing');

    const formData = new FormData();
    const ext = (blob.type && blob.type.includes('mp4')) ? 'mp4' : 'webm';
    formData.append('audio', blob, 'recording.' + ext);
    if (durationSec) formData.append('duration_seconds', durationSec);

    const anonCb = document.getElementById('is_anonymous');
    if (anonCb && anonCb.checked) {
      formData.append('anonymous', '1');
    }
    if (mockScenario) {
      formData.append('mock_scenario', mockScenario);
    }

    try {
      const response = await fetch(endpointUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: formData,
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || 'Analysis failed');
      }

      const data = await response.json();

      tokenInput.value = data.token || '';
      audioPreview.src = URL.createObjectURL(blob);

      // Dispatch event for parent intake forms (record-first flow)
      const eventPayload = Object.assign({}, data, { blob: blob });
      window.dispatchEvent(new CustomEvent('voice-analyzed', { detail: eventPayload }));
      container.dispatchEvent(new CustomEvent('voice-analyzed', { bubbles: true, detail: eventPayload }));

      if (data.status === 'needs_verification' || data.status === 'failed' || data.unclear || data.is_unclear) {
        noticeCard.style.display = 'block';
        if (data.notice) noticeText.textContent = data.notice;
        // Uncertainty Rule: do not insert guessed text. If empty, put standard note.
        if (targetInput && (!targetInput.value || targetInput.value.trim() === '')) {
          targetInput.value = '(Voice recording attached — needs staff audio verification)';
          targetInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
      } else {
        noticeCard.style.display = 'none';
        if (targetInput && data.description && (!targetInput.value || targetInput.value.trim() === '' || targetInput.dataset.aiFilled === 'true')) {
          targetInput.value = data.description;
          targetInput.dataset.aiFilled = 'true';
          targetInput.dispatchEvent(new Event('input', { bubbles: true }));
        } else if (targetInput && data.transcript && (!targetInput.value || targetInput.value.trim() === '')) {
          targetInput.value = data.transcript;
          targetInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
      }

      showState('ready');
    } catch (err) {
      alert('Could not process audio: ' + err.message);
      showState('idle');
    }
  }

  btnRecord.addEventListener('click', startRecording);
  btnStop.addEventListener('click', stopRecording);
  btnCancel.addEventListener('click', cancelRecording);

  btnReset.addEventListener('click', () => {
    tokenInput.value = '';
    audioPreview.src = '';
    noticeCard.style.display = 'none';
    window.dispatchEvent(new CustomEvent('voice-reset'));
    container.dispatchEvent(new CustomEvent('voice-reset', { bubbles: true }));
    showState('idle');
  });

  fileInput.addEventListener('change', (e) => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    uploadAndTranscribe(file, 10);
  });

  let demoIndex = 0;
  const demoScenarios = ['full', 'partial', 'no_contact', 'emergency', 'unclear'];

  if (btnDemo) {
    btnDemo.addEventListener('click', () => {
      // Create empty mock audio buffer
      const mockWav = new Uint8Array([82,73,70,70,36,0,0,0,87,65,86,69,102,109,116,32,16,0,0,0,1,0,1,0,68,172,0,0,136,88,1,0,2,0,16,0,100,97,116,97,0,0,0,0]);
      const blob = new Blob([mockWav], { type: 'audio/wav' });
      const scenario = demoScenarios[demoIndex % demoScenarios.length];
      demoIndex++;
      uploadAndTranscribe(blob, 15, scenario);
    });
  }
})();
</script>
