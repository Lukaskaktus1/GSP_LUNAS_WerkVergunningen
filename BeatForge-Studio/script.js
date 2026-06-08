const TRACK_COUNT = 8;
const STEP_COUNT = 16;
const STORAGE_KEY = "beatforge-pattern-v1";

const defaultSamples = [
  { name: "Kick", path: "samples/kick.wav", type: "kick" },
  { name: "Snare", path: "samples/snare.wav", type: "snare" },
  { name: "Clap", path: "samples/clap.wav", type: "clap" },
  { name: "Hi-hat", path: "samples/hihat.wav", type: "hat" },
  { name: "Bass", path: "samples/bass.wav", type: "bass" },
  { name: "Melody", path: "samples/melody.wav", type: "melody" }
];

const state = {
  audioContext: null,
  isPlaying: false,
  currentStep: 0,
  timerId: null,
  selectedTrack: 0,
  samples: [],
  tracks: [],
  pattern: []
};

const elements = {
  playButton: document.getElementById("playButton"),
  stopButton: document.getElementById("stopButton"),
  resetButton: document.getElementById("resetButton"),
  fillButton: document.getElementById("fillButton"),
  clearTrackButton: document.getElementById("clearTrackButton"),
  saveButton: document.getElementById("saveButton"),
  loadButton: document.getElementById("loadButton"),
  bpmInput: document.getElementById("bpmInput"),
  statusText: document.getElementById("statusText"),
  sampleUpload: document.getElementById("sampleUpload"),
  sampleList: document.getElementById("sampleList"),
  sampleCount: document.getElementById("sampleCount"),
  trackPicker: document.getElementById("trackPicker"),
  trackLabels: document.getElementById("trackLabels"),
  stepHeader: document.getElementById("stepHeader"),
  stepGrid: document.getElementById("stepGrid"),
  mixerList: document.getElementById("mixerList"),
  timelineSteps: document.getElementById("timelineSteps"),
  stepReadout: document.getElementById("stepReadout")
};

function initializeStudio() {
  state.samples = defaultSamples.map((sample, index) => ({
    ...sample,
    id: `default-${index}`,
    buffer: null,
    loadFailed: false
  }));

  state.tracks = Array.from({ length: TRACK_COUNT }, (_, index) => ({
    id: index,
    sampleId: state.samples[index % state.samples.length].id,
    volume: 0.82,
    muted: false
  }));

  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));

  renderTimeline();
  renderStepHeader();
  renderTrackPicker();
  renderSampleList();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  bindEvents();
  updatePlaybackHighlight();
}

function bindEvents() {
  elements.playButton.addEventListener("click", startPlayback);
  elements.stopButton.addEventListener("click", stopPlayback);
  elements.resetButton.addEventListener("click", resetPattern);
  elements.fillButton.addEventListener("click", fillStarterBeat);
  elements.clearTrackButton.addEventListener("click", clearSelectedTrack);
  elements.saveButton.addEventListener("click", savePattern);
  elements.loadButton.addEventListener("click", loadPattern);
  elements.bpmInput.addEventListener("change", clampBpm);
  elements.sampleUpload.addEventListener("change", handleSampleUpload);
}

function renderTimeline() {
  elements.timelineSteps.innerHTML = "";

  for (let step = 0; step < STEP_COUNT; step += 1) {
    const marker = document.createElement("div");
    marker.className = "timeline-step";
    marker.dataset.step = step;
    marker.textContent = String(step + 1).padStart(2, "0");
    elements.timelineSteps.appendChild(marker);
  }
}

function renderStepHeader() {
  elements.stepHeader.innerHTML = "<div></div>";

  for (let step = 0; step < STEP_COUNT; step += 1) {
    const stepNumber = document.createElement("div");
    stepNumber.className = `step-number${step % 4 === 0 ? " downbeat" : ""}`;
    stepNumber.textContent = step + 1;
    elements.stepHeader.appendChild(stepNumber);
  }
}

function renderTrackPicker() {
  elements.trackPicker.innerHTML = "";

  state.tracks.forEach((track, index) => {
    const button = document.createElement("button");
    button.className = `track-select-button${state.selectedTrack === index ? " selected" : ""}`;
    button.type = "button";
    button.textContent = `T${index + 1}`;
    button.addEventListener("click", () => {
      selectTrack(index);
    });
    elements.trackPicker.appendChild(button);
  });
}

function renderSampleList() {
  elements.sampleList.innerHTML = "";
  elements.sampleCount.textContent = state.samples.length;

  state.samples.forEach((sample) => {
    const item = document.createElement("li");
    item.className = "sample-item";
    item.draggable = true;
    item.dataset.sampleId = sample.id;

    const details = document.createElement("div");
    details.className = "sample-meta";
    details.innerHTML = `
      <div class="sample-topline">
        <span class="sample-name">${escapeHtml(sample.name)}</span>
        <span class="sample-type">${escapeHtml(sample.type || "sound")}</span>
      </div>
      <span class="sample-path">${escapeHtml(sample.path || "Uploaded sample")}</span>
    `;

    const actions = document.createElement("div");
    actions.className = "sample-actions";

    const previewButton = document.createElement("button");
    previewButton.className = "preview-button";
    previewButton.type = "button";
    previewButton.textContent = "Play";
    previewButton.addEventListener("click", () => previewSample(sample.id));

    const assignButton = document.createElement("button");
    assignButton.type = "button";
    assignButton.textContent = "Use";
    assignButton.addEventListener("click", () => assignSampleToTrack(sample.id, state.selectedTrack));

    item.addEventListener("dragstart", (event) => {
      event.dataTransfer.setData("text/plain", sample.id);
      event.dataTransfer.effectAllowed = "copy";
      item.classList.add("dragging");
      setStatus(`${sample.name} wordt gesleept`);
    });

    item.addEventListener("dragend", () => {
      item.classList.remove("dragging");
      clearDropTargets();
    });

    actions.append(previewButton, assignButton);
    item.append(details, actions);
    elements.sampleList.appendChild(item);
  });
}

function renderTrackLabels() {
  elements.trackLabels.innerHTML = "";

  state.tracks.forEach((track, index) => {
    const sample = getSampleById(track.sampleId);
    const row = document.createElement("div");
    row.className = `track-row${state.selectedTrack === index ? " selected" : ""}`;
    row.dataset.track = index;
    row.innerHTML = `<span>${index + 1}. ${escapeHtml(sample?.name || "Empty")}</span>`;
    row.addEventListener("click", () => selectTrack(index));
    row.addEventListener("dragover", (event) => {
      event.preventDefault();
      row.classList.add("drop-target");
    });
    row.addEventListener("dragleave", () => row.classList.remove("drop-target"));
    row.addEventListener("drop", (event) => {
      event.preventDefault();
      const sampleId = event.dataTransfer.getData("text/plain");
      row.classList.remove("drop-target");
      assignSampleToTrack(sampleId, index);
    });
    elements.trackLabels.appendChild(row);
  });
}

function renderStepGrid() {
  elements.stepGrid.innerHTML = "";

  for (let track = 0; track < TRACK_COUNT; track += 1) {
    for (let step = 0; step < STEP_COUNT; step += 1) {
      const cell = document.createElement("button");
      cell.className = `step-cell${state.pattern[track][step] ? " active" : ""}`;
      cell.type = "button";
      cell.dataset.track = track;
      cell.dataset.step = step;
      cell.title = `Track ${track + 1}, step ${step + 1}`;
      cell.addEventListener("click", () => toggleStep(track, step));
      cell.addEventListener("dragover", (event) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = "copy";
        cell.classList.add("drop-target");
      });
      cell.addEventListener("dragleave", () => cell.classList.remove("drop-target"));
      cell.addEventListener("drop", (event) => {
        event.preventDefault();
        const sampleId = event.dataTransfer.getData("text/plain");
        cell.classList.remove("drop-target");
        placeSampleOnStep(sampleId, track, step);
      });
      elements.stepGrid.appendChild(cell);
    }
  }
}

function renderMixer() {
  elements.mixerList.innerHTML = "";

  state.tracks.forEach((track, index) => {
    const sample = getSampleById(track.sampleId);
    const channel = document.createElement("div");
    channel.className = "mixer-channel";

    channel.innerHTML = `
      <div class="channel-index">${index + 1}</div>
      <div class="channel-main">
        <span class="channel-name">${escapeHtml(sample?.name || "Empty")}</span>
        <div class="volume-row">
          <input type="range" min="0" max="1" step="0.01" value="${track.volume}" aria-label="Volume track ${index + 1}">
          <span class="volume-value">${Math.round(track.volume * 100)}%</span>
        </div>
      </div>
      <button class="mute-button${track.muted ? " muted" : ""}" type="button" title="Mute track ${index + 1}">M</button>
    `;

    const volumeInput = channel.querySelector("input");
    const volumeValue = channel.querySelector(".volume-value");
    const muteButton = channel.querySelector(".mute-button");

    volumeInput.addEventListener("input", () => {
      track.volume = Number(volumeInput.value);
      volumeValue.textContent = `${Math.round(track.volume * 100)}%`;
    });

    muteButton.addEventListener("click", () => {
      track.muted = !track.muted;
      muteButton.classList.toggle("muted", track.muted);
      setStatus(track.muted ? `Track ${index + 1} muted` : `Track ${index + 1} unmuted`);
    });

    elements.mixerList.appendChild(channel);
  });
}

function toggleStep(track, step) {
  state.pattern[track][step] = !state.pattern[track][step];
  const cell = elements.stepGrid.querySelector(`[data-track="${track}"][data-step="${step}"]`);
  cell.classList.toggle("active", state.pattern[track][step]);
}

function selectTrack(trackIndex) {
  state.selectedTrack = trackIndex;
  renderTrackPicker();
  renderTrackLabels();
  setStatus(`Track ${trackIndex + 1} geselecteerd`);
}

function placeSampleOnStep(sampleId, trackIndex, stepIndex) {
  const sample = getSampleById(sampleId);

  if (!sample) {
    return;
  }

  state.selectedTrack = trackIndex;
  state.tracks[trackIndex].sampleId = sampleId;
  state.pattern[trackIndex][stepIndex] = true;

  renderTrackPicker();
  renderTrackLabels();
  renderMixer();

  const cell = elements.stepGrid.querySelector(`[data-track="${trackIndex}"][data-step="${stepIndex}"]`);
  cell?.classList.add("active");
  setStatus(`${sample.name} op track ${trackIndex + 1}, step ${stepIndex + 1}`);
}

function assignSampleToTrack(sampleId, trackIndex) {
  const sample = getSampleById(sampleId);

  if (!sample) {
    return;
  }

  state.tracks[trackIndex].sampleId = sampleId;
  state.selectedTrack = trackIndex;
  renderTrackPicker();
  renderTrackLabels();
  renderMixer();
  setStatus(`${sample.name} gekoppeld aan track ${trackIndex + 1}`);
}

async function previewSample(sampleId) {
  const sample = getSampleById(sampleId);

  if (!sample) {
    return;
  }

  await ensureAudioContext();

  if (state.audioContext.state === "suspended") {
    await state.audioContext.resume();
  }

  if (!sample.buffer && !sample.loadFailed) {
    await loadSampleBuffer(sample);
  }

  if (sample.buffer) {
    const source = state.audioContext.createBufferSource();
    const gain = state.audioContext.createGain();

    source.buffer = sample.buffer;
    gain.gain.value = 0.9;
    source.connect(gain);
    gain.connect(state.audioContext.destination);
    source.start();
  } else {
    playFallbackSound(sample.type, 0.9);
  }

  setStatus(`${sample.name} preview`);
}

function clearDropTargets() {
  document.querySelectorAll(".drop-target").forEach((element) => element.classList.remove("drop-target"));
}

function fillStarterBeat() {
  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));

  state.tracks.forEach((track, trackIndex) => {
    const sample = getSampleById(track.sampleId);
    const type = sample?.type || "";
    const name = sample?.name.toLowerCase() || "";
    let steps = [];

    if (type === "kick" || name.includes("kick")) {
      steps = [0, 4, 8, 12];
    } else if (type === "snare" || name.includes("snare")) {
      steps = [4, 12];
    } else if (type === "clap" || name.includes("clap")) {
      steps = [6, 14];
    } else if (type === "hat" || name.includes("hat")) {
      steps = [2, 4, 6, 8, 10, 12, 14];
    } else if (type === "bass" || name.includes("bass")) {
      steps = [0, 3, 7, 10, 14];
    } else if (type === "melody" || name.includes("melody")) {
      steps = [2, 6, 10, 15];
    }

    steps.forEach((step) => {
      state.pattern[trackIndex][step] = true;
    });
  });

  renderStepGrid();
  updatePlaybackHighlight();
  setStatus("Starter beat geplaatst");
}

function clearSelectedTrack() {
  state.pattern[state.selectedTrack] = Array(STEP_COUNT).fill(false);
  renderStepGrid();
  updatePlaybackHighlight();
  setStatus(`Track ${state.selectedTrack + 1} leeggemaakt`);
}

async function startPlayback() {
  await ensureAudioContext();

  if (state.audioContext.state === "suspended") {
    await state.audioContext.resume();
  }

  if (state.isPlaying) {
    return;
  }

  await preloadDefaultSamples();
  state.isPlaying = true;
  setStatus("Aan het spelen");

  // The first hit plays immediately; the timeout loop advances from there.
  playCurrentStep();
  scheduleNextStep();
}

function scheduleNextStep() {
  clearTimeout(state.timerId);
  state.timerId = window.setTimeout(() => {
    state.currentStep = (state.currentStep + 1) % STEP_COUNT;
    playCurrentStep();
    scheduleNextStep();
  }, getStepDuration());
}

function playCurrentStep() {
  updatePlaybackHighlight();

  state.tracks.forEach((track, trackIndex) => {
    if (!state.pattern[trackIndex][state.currentStep] || track.muted) {
      return;
    }

    playTrackSample(track);
  });
}

function stopPlayback() {
  clearTimeout(state.timerId);
  state.timerId = null;
  state.isPlaying = false;
  state.currentStep = 0;
  updatePlaybackHighlight();
  setStatus("Gestopt");
}

function updatePlaybackHighlight() {
  elements.stepReadout.textContent = `Step ${String(state.currentStep + 1).padStart(2, "0")}`;

  document.querySelectorAll(".step-cell.playing").forEach((cell) => cell.classList.remove("playing"));
  document.querySelectorAll(".timeline-step.playing").forEach((step) => step.classList.remove("playing"));

  document.querySelectorAll(`.step-cell[data-step="${state.currentStep}"]`).forEach((cell) => {
    cell.classList.add("playing");
  });

  const timelineStep = elements.timelineSteps.querySelector(`[data-step="${state.currentStep}"]`);
  timelineStep?.classList.add("playing");
}

async function playTrackSample(track) {
  await ensureAudioContext();
  const sample = getSampleById(track.sampleId);

  if (!sample) {
    return;
  }

  if (!sample.buffer && !sample.loadFailed) {
    await loadSampleBuffer(sample);
  }

  if (sample.buffer) {
    const source = state.audioContext.createBufferSource();
    const gain = state.audioContext.createGain();

    source.buffer = sample.buffer;
    gain.gain.value = track.volume;
    source.connect(gain);
    gain.connect(state.audioContext.destination);
    source.start();
    return;
  }

  playFallbackSound(sample.type, track.volume);
}

async function ensureAudioContext() {
  if (!state.audioContext) {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    state.audioContext = new AudioContextClass();
  }
}

async function preloadDefaultSamples() {
  await Promise.all(
    state.samples
      .filter((sample) => sample.path && !sample.buffer && !sample.loadFailed)
      .map((sample) => loadSampleBuffer(sample))
  );
}

async function loadSampleBuffer(sample) {
  if (!sample.path) {
    return;
  }

  try {
    const response = await fetch(sample.path);

    if (!response.ok) {
      throw new Error(`Sample not found: ${sample.path}`);
    }

    const arrayBuffer = await response.arrayBuffer();
    sample.buffer = await state.audioContext.decodeAudioData(arrayBuffer);
  } catch (error) {
    sample.loadFailed = true;
    console.info(`${sample.name} uses generated fallback audio. Place a WAV file at ${sample.path} to use your own sample.`);
  }
}

function playFallbackSound(type, volume) {
  const now = state.audioContext.currentTime;
  const safeVolume = Math.max(0.001, volume);
  const oscillator = state.audioContext.createOscillator();
  const gain = state.audioContext.createGain();
  const filter = state.audioContext.createBiquadFilter();

  oscillator.connect(filter);
  filter.connect(gain);
  gain.connect(state.audioContext.destination);

  // Fallback sounds keep the app playable until real WAV files are added.
  gain.gain.setValueAtTime(safeVolume, now);

  if (type === "kick") {
    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime(140, now);
    oscillator.frequency.exponentialRampToValueAtTime(45, now + 0.18);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.24);
    oscillator.start(now);
    oscillator.stop(now + 0.25);
    return;
  }

  if (type === "snare" || type === "clap") {
    oscillator.type = "triangle";
    oscillator.frequency.value = type === "clap" ? 900 : 220;
    filter.type = "highpass";
    filter.frequency.value = 650;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
    oscillator.start(now);
    oscillator.stop(now + 0.13);
    return;
  }

  if (type === "hat") {
    oscillator.type = "square";
    oscillator.frequency.value = 7500;
    filter.type = "highpass";
    filter.frequency.value = 5000;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.055);
    oscillator.start(now);
    oscillator.stop(now + 0.06);
    return;
  }

  oscillator.type = "sawtooth";
  oscillator.frequency.value = type === "bass" ? 82 : 330;
  filter.type = "lowpass";
  filter.frequency.value = type === "bass" ? 420 : 1600;
  gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
  oscillator.start(now);
  oscillator.stop(now + 0.24);
}

async function handleSampleUpload(event) {
  await ensureAudioContext();
  const files = Array.from(event.target.files);

  for (const file of files) {
    try {
      const arrayBuffer = await file.arrayBuffer();
      const buffer = await state.audioContext.decodeAudioData(arrayBuffer);
      const sample = {
        id: `upload-${crypto.randomUUID()}`,
        name: file.name.replace(/\.[^/.]+$/, ""),
        path: file.name,
        type: "uploaded",
        buffer,
        loadFailed: false
      };
      const savedSample = await saveSampleOnServer(file);

      if (savedSample) {
        sample.name = savedSample.name;
        sample.path = savedSample.path;
      }

      state.samples.push(sample);
    } catch (error) {
      setStatus(`${file.name} could not be loaded`);
    }
  }

  event.target.value = "";
  renderSampleList();
  setStatus(`${files.length} sample${files.length === 1 ? "" : "s"} added`);
}

async function saveSampleOnServer(file) {
  if (!window.location.protocol.startsWith("http")) {
    return null;
  }

  try {
    const data = new FormData();
    data.append("sample", file);

    const response = await fetch("upload.php", {
      method: "POST",
      body: data
    });

    if (!response.ok) {
      return null;
    }

    return await response.json();
  } catch (error) {
    return null;
  }
}

function getStepDuration() {
  const bpm = clampBpm();
  return (60 / bpm / 4) * 1000;
}

function clampBpm() {
  const bpm = Math.min(220, Math.max(60, Number(elements.bpmInput.value) || 120));
  elements.bpmInput.value = bpm;
  return bpm;
}

function resetPattern() {
  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  renderStepGrid();
  updatePlaybackHighlight();
  setStatus("Pattern leeggemaakt");
}

function savePattern() {
  const data = {
    bpm: clampBpm(),
    selectedTrack: state.selectedTrack,
    tracks: state.tracks.map((track) => ({
      sampleId: track.sampleId,
      volume: track.volume,
      muted: track.muted
    })),
    pattern: state.pattern
  };

  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  setStatus("Pattern opgeslagen");
}

function loadPattern() {
  const saved = localStorage.getItem(STORAGE_KEY);

  if (!saved) {
    setStatus("Geen opgeslagen pattern gevonden");
    return;
  }

  try {
    const data = JSON.parse(saved);
    elements.bpmInput.value = data.bpm || 120;
    state.selectedTrack = data.selectedTrack || 0;
    state.pattern = normalizePattern(data.pattern);
    state.tracks = normalizeTracks(data.tracks);

    renderTrackPicker();
    renderTrackLabels();
    renderStepGrid();
    renderMixer();
    updatePlaybackHighlight();
    setStatus("Pattern geladen");
  } catch (error) {
    setStatus("Opgeslagen pattern kon niet laden");
  }
}

function normalizePattern(pattern) {
  return Array.from({ length: TRACK_COUNT }, (_, trackIndex) => {
    const row = Array.isArray(pattern?.[trackIndex]) ? pattern[trackIndex] : [];
    return Array.from({ length: STEP_COUNT }, (_, stepIndex) => Boolean(row[stepIndex]));
  });
}

function normalizeTracks(tracks) {
  return Array.from({ length: TRACK_COUNT }, (_, index) => {
    const savedTrack = tracks?.[index] || {};
    const sampleExists = state.samples.some((sample) => sample.id === savedTrack.sampleId);

    return {
      id: index,
      sampleId: sampleExists ? savedTrack.sampleId : state.tracks[index].sampleId,
      volume: Number.isFinite(savedTrack.volume) ? savedTrack.volume : state.tracks[index].volume,
      muted: Boolean(savedTrack.muted)
    };
  });
}

function getSampleById(sampleId) {
  return state.samples.find((sample) => sample.id === sampleId);
}

function setStatus(message) {
  elements.statusText.textContent = message;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

initializeStudio();
