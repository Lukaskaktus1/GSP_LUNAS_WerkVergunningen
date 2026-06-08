const TRACK_COUNT = 8;
const STORAGE_KEY = "beatforge-pattern-v2";
const MIN_STEPS = 8;
const MAX_STEPS = 32;

let STEP_COUNT = 16;

const categoryConfig = [
  ["all", "All"],
  ["kick", "Kicks"],
  ["snare", "Snares"],
  ["clap", "Claps"],
  ["hat", "Hi-hats"],
  ["bass", "Bass"],
  ["melody", "Melody"],
  ["fx", "FX"],
  ["piano", "Piano"],
  ["recording", "Recordings"],
  ["uploaded", "Uploads"]
];

const defaultSamples = [
  { name: "Kick Classic", path: "samples/kick.wav", type: "kick", tone: "classic" },
  { name: "Kick Deep 808", path: null, type: "kick", tone: "deep" },
  { name: "Kick Punch", path: null, type: "kick", tone: "punch" },
  { name: "Kick Sub Clean", path: null, type: "kick", tone: "sub" },
  { name: "Snare Tight", path: "samples/snare.wav", type: "snare", tone: "tight" },
  { name: "Snare Body", path: null, type: "snare", tone: "body" },
  { name: "Snare Bright", path: null, type: "snare", tone: "bright" },
  { name: "Clap Studio", path: "samples/clap.wav", type: "clap", tone: "studio" },
  { name: "Clap Wide", path: null, type: "clap", tone: "wide" },
  { name: "Hi-hat Closed", path: "samples/hihat.wav", type: "hat", tone: "closed" },
  { name: "Hi-hat Open", path: null, type: "hat", tone: "open" },
  { name: "Hi-hat Tick", path: null, type: "hat", tone: "tick" },
  { name: "Bass Warm", path: "samples/bass.wav", type: "bass", tone: "warm" },
  { name: "Bass Acid", path: null, type: "bass", tone: "acid" },
  { name: "Bass Sub", path: null, type: "bass", tone: "sub" },
  { name: "Lead Pluck", path: "samples/melody.wav", type: "melody", tone: "pluck" },
  { name: "Lead Soft", path: null, type: "melody", tone: "soft" },
  { name: "Pad Warm", path: null, type: "melody", tone: "pad" },
  { name: "FX Riser", path: null, type: "fx", tone: "riser" },
  { name: "FX Impact", path: null, type: "fx", tone: "impact" }
];

const pianoNotes = [
  "C6", "B5", "A#5", "A5", "G#5", "G5", "F#5", "F5", "E5", "D#5", "D5", "C#5",
  "C5", "B4", "A#4", "A4", "G#4", "G4", "F#4", "F4", "E4", "D#4", "D4", "C#4",
  "C4", "B3", "A#3", "A3", "G#3", "G3", "F#3", "F3", "E3", "D#3", "D3", "C#3", "C3"
];

const state = {
  audioContext: null,
  isPlaying: false,
  currentStep: 0,
  timerId: null,
  selectedTrack: 0,
  samples: [],
  tracks: [],
  pattern: [],
  lengths: [],
  dragPaint: null,
  gridGesture: null,
  pianoGesture: null,
  skipNextClick: false,
  mediaRecorder: null,
  recordedChunks: [],
  pianoNotes: []
};

const elements = {
  playButton: document.getElementById("playButton"),
  stopButton: document.getElementById("stopButton"),
  resetButton: document.getElementById("resetButton"),
  fillButton: document.getElementById("fillButton"),
  randomTrackButton: document.getElementById("randomTrackButton"),
  shiftLeftButton: document.getElementById("shiftLeftButton"),
  shiftRightButton: document.getElementById("shiftRightButton"),
  clearTrackButton: document.getElementById("clearTrackButton"),
  accentButton: document.getElementById("accentButton"),
  addStepButton: document.getElementById("addStepButton"),
  removeStepButton: document.getElementById("removeStepButton"),
  normalizeButton: document.getElementById("normalizeButton"),
  unmuteAllButton: document.getElementById("unmuteAllButton"),
  themeButton: document.getElementById("themeButton"),
  showStudioButton: document.getElementById("showStudioButton"),
  showPianoButton: document.getElementById("showPianoButton"),
  backToStudioButton: document.getElementById("backToStudioButton"),
  saveButton: document.getElementById("saveButton"),
  loadButton: document.getElementById("loadButton"),
  bpmInput: document.getElementById("bpmInput"),
  swingInput: document.getElementById("swingInput"),
  densityInput: document.getElementById("densityInput"),
  densityValue: document.getElementById("densityValue"),
  categoryFilter: document.getElementById("categoryFilter"),
  categoryTabs: document.getElementById("categoryTabs"),
  manualSoundName: document.getElementById("manualSoundName"),
  manualSoundType: document.getElementById("manualSoundType"),
  addSoundButton: document.getElementById("addSoundButton"),
  recordSoundName: document.getElementById("recordSoundName"),
  recordSoundType: document.getElementById("recordSoundType"),
  recordButton: document.getElementById("recordButton"),
  stopRecordButton: document.getElementById("stopRecordButton"),
  recordStatus: document.getElementById("recordStatus"),
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
  stepReadout: document.getElementById("stepReadout"),
  selectedTrackName: document.getElementById("selectedTrackName"),
  activeStepCount: document.getElementById("activeStepCount"),
  stepDepthValue: document.getElementById("stepDepthValue"),
  modifierStatus: document.getElementById("modifierStatus"),
  stepCountMeta: document.getElementById("stepCountMeta"),
  masterMeter: document.getElementById("masterMeter"),
  pianoPage: document.getElementById("pianoPage"),
  pianoNameInput: document.getElementById("pianoNameInput"),
  pianoStepsInput: document.getElementById("pianoStepsInput"),
  pianoLengthInput: document.getElementById("pianoLengthInput"),
  pianoKeys: document.getElementById("pianoKeys"),
  pianoRollGrid: document.getElementById("pianoRollGrid"),
  playPianoButton: document.getElementById("playPianoButton"),
  clearPianoButton: document.getElementById("clearPianoButton"),
  addPianoSoundButton: document.getElementById("addPianoSoundButton")
};

function initializeStudio() {
  state.samples = defaultSamples.map((sample, index) => ({
    ...sample,
    id: `default-${index}`,
    source: "default",
    buffer: null,
    loadFailed: false
  }));

  const starterTypes = ["kick", "snare", "clap", "hat", "hat", "bass", "melody", "fx"];
  state.tracks = Array.from({ length: TRACK_COUNT }, (_, index) => {
    const sample = state.samples.find((item) => item.type === starterTypes[index]) || state.samples[index];
    return { id: index, sampleId: sample.id, volume: 0.82, muted: false };
  });

  resetGrids();
  bindEvents();
  applyGridSizing();
  renderEverything();
  setStatus("Klaar");
}

function bindEvents() {
  elements.playButton.addEventListener("click", startPlayback);
  elements.stopButton.addEventListener("click", stopPlayback);
  elements.resetButton.addEventListener("click", resetPattern);
  elements.fillButton.addEventListener("click", fillStarterBeat);
  elements.randomTrackButton.addEventListener("click", randomizeSelectedTrack);
  elements.shiftLeftButton.addEventListener("click", () => shiftSelectedTrack(-1));
  elements.shiftRightButton.addEventListener("click", () => shiftSelectedTrack(1));
  elements.clearTrackButton.addEventListener("click", clearSelectedTrack);
  elements.accentButton.addEventListener("click", accentCurrentStep);
  elements.addStepButton.addEventListener("click", () => resizeSteps(STEP_COUNT + 1));
  elements.removeStepButton.addEventListener("click", () => resizeSteps(STEP_COUNT - 1));
  elements.normalizeButton.addEventListener("click", normalizeMixer);
  elements.unmuteAllButton.addEventListener("click", unmuteAllTracks);
  elements.themeButton.addEventListener("click", toggleTheme);
  elements.showStudioButton.addEventListener("click", showStudioView);
  elements.showPianoButton.addEventListener("click", showPianoView);
  elements.backToStudioButton.addEventListener("click", showStudioView);
  elements.saveButton.addEventListener("click", savePattern);
  elements.loadButton.addEventListener("click", loadPattern);
  elements.bpmInput.addEventListener("change", clampBpm);
  elements.swingInput.addEventListener("change", clampSwing);
  elements.densityInput.addEventListener("input", updateDensityReadout);
  elements.categoryFilter.addEventListener("change", () => {
    renderCategoryTabs();
    renderSampleList();
  });
  elements.addSoundButton.addEventListener("click", addManualSound);
  elements.sampleUpload.addEventListener("change", handleSampleUpload);
  elements.recordButton.addEventListener("click", startRecording);
  elements.stopRecordButton.addEventListener("click", stopRecording);
  elements.pianoStepsInput.addEventListener("change", renderPianoRoll);
  elements.pianoLengthInput.addEventListener("change", clampPianoLength);
  elements.playPianoButton.addEventListener("click", playPianoPattern);
  elements.clearPianoButton.addEventListener("click", clearPianoPattern);
  elements.addPianoSoundButton.addEventListener("click", addPianoSound);
  window.addEventListener("mouseup", endGestures);
  window.addEventListener("keydown", (event) => updateModifierStatus(null, event));
  window.addEventListener("keyup", (event) => updateModifierStatus(null, event));
}

function renderEverything() {
  renderMasterMeter();
  renderTimeline();
  renderStepHeader();
  renderCategoryTabs();
  renderTrackPicker();
  renderSampleList();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  renderPianoRoll();
  updateDensityReadout();
  updateStudioOverview();
  updatePlaybackHighlight();
}

function resetGrids() {
  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.lengths = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));
}

function renderMasterMeter() {
  elements.masterMeter.innerHTML = "";
  for (let index = 0; index < 24; index += 1) {
    const bar = document.createElement("span");
    bar.className = "meter-bar";
    bar.style.height = `${20 + ((index * 13) % 58)}%`;
    elements.masterMeter.appendChild(bar);
  }
}

function renderTimeline() {
  elements.timelineSteps.innerHTML = "";
  elements.timelineSteps.style.gridTemplateColumns = `repeat(${STEP_COUNT}, minmax(28px, 1fr))`;
  elements.stepCountMeta.textContent = `${STEP_COUNT} steps`;
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
    const number = document.createElement("div");
    number.className = `step-number${step % 4 === 0 ? " downbeat" : ""}`;
    number.textContent = step + 1;
    elements.stepHeader.appendChild(number);
  }
}

function renderCategoryTabs() {
  elements.categoryTabs.innerHTML = "";
  const selected = elements.categoryFilter.value;
  categoryConfig.forEach(([value, label]) => {
    const count = value === "all" ? state.samples.length : state.samples.filter((sample) => sample.type === value).length;
    const button = document.createElement("button");
    button.className = `category-tab${selected === value ? " active" : ""}`;
    button.type = "button";
    button.textContent = `${label} ${count}`;
    button.addEventListener("click", () => {
      elements.categoryFilter.value = value;
      renderCategoryTabs();
      renderSampleList();
    });
    elements.categoryTabs.appendChild(button);
  });
}

function renderSampleList() {
  elements.sampleList.innerHTML = "";
  const category = elements.categoryFilter.value;
  const samples = category === "all" ? state.samples : state.samples.filter((sample) => sample.type === category);
  elements.sampleCount.textContent = samples.length;

  samples.forEach((sample) => {
    const item = document.createElement("li");
    item.className = "sample-item";
    item.draggable = true;
    item.dataset.sampleId = sample.id;

    const details = document.createElement("div");
    details.className = "sample-meta";
    details.innerHTML = `
      <div class="sample-topline">
        <span class="sample-name">${escapeHtml(sample.name)}</span>
        <span class="sample-type">${escapeHtml(typeLabel(sample.type))}</span>
      </div>
      <span class="sample-path">${escapeHtml(getSampleSubtitle(sample))}</span>
    `;
    details.appendChild(createWaveformElement(sample));

    const actions = document.createElement("div");
    actions.className = "sample-actions";
    actions.append(createSampleButton("Play", "preview-button", () => previewSample(sample.id)));
    actions.append(createSampleButton("Use", "", () => assignSampleToTrack(sample.id, state.selectedTrack)));

    if (canDeleteSample(sample)) {
      actions.append(createSampleButton("Delete", "delete-button", () => deleteSample(sample.id)));
    }

    item.addEventListener("dragstart", (event) => {
      event.dataTransfer.setData("text/plain", sample.id);
      event.dataTransfer.effectAllowed = "copy";
      item.classList.add("dragging");
      state.dragPaint = null;
      setStatus(`${sample.name} wordt gesleept`);
    });
    item.addEventListener("dragend", () => {
      item.classList.remove("dragging");
      clearDropTargets();
      state.dragPaint = null;
    });

    item.append(details, actions);
    elements.sampleList.appendChild(item);
  });
}

function createSampleButton(text, className, onClick) {
  const button = document.createElement("button");
  button.type = "button";
  button.textContent = text;
  if (className) {
    button.className = className;
  }
  button.addEventListener("click", onClick);
  return button;
}

function createWaveformElement(sample) {
  const waveform = document.createElement("div");
  waveform.className = "waveform-strip";
  waveform.setAttribute("aria-hidden", "true");
  for (let index = 0; index < 18; index += 1) {
    const bar = document.createElement("span");
    const seed = sample.name.length * 7 + index * 11 + (sample.tone || sample.type).length * 5;
    bar.style.height = `${22 + (seed % 70)}%`;
    waveform.appendChild(bar);
  }
  return waveform;
}

function renderTrackPicker() {
  elements.trackPicker.innerHTML = "";
  state.tracks.forEach((track, index) => {
    const button = document.createElement("button");
    button.className = `track-select-button${state.selectedTrack === index ? " selected" : ""}`;
    button.type = "button";
    button.textContent = `T${index + 1}`;
    button.addEventListener("click", () => selectTrack(index));
    elements.trackPicker.appendChild(button);
  });
}

function renderTrackLabels() {
  elements.trackLabels.innerHTML = "";
  state.tracks.forEach((track, index) => {
    const sample = getSampleById(track.sampleId);
    const row = document.createElement("div");
    row.className = `track-row${state.selectedTrack === index ? " selected" : ""}`;
    row.dataset.track = index;
    row.innerHTML = `
      <span class="track-number">${index + 1}</span>
      <span>
        <strong>${escapeHtml(sample?.name || "Empty")}</strong>
        <small>${countActiveSteps(index)} steps actief</small>
      </span>
      <span class="track-chip">${escapeHtml(typeLabel(sample?.type || "sound"))}</span>
    `;
    row.addEventListener("click", () => selectTrack(index));
    row.addEventListener("dragover", (event) => {
      event.preventDefault();
      row.classList.add("drop-target");
    });
    row.addEventListener("dragleave", () => row.classList.remove("drop-target"));
    row.addEventListener("drop", (event) => {
      event.preventDefault();
      row.classList.remove("drop-target");
      assignSampleToTrack(event.dataTransfer.getData("text/plain"), index);
    });
    elements.trackLabels.appendChild(row);
  });
}

function renderStepGrid() {
  elements.stepGrid.innerHTML = "";
  for (let track = 0; track < TRACK_COUNT; track += 1) {
    for (let step = 0; step < STEP_COUNT; step += 1) {
      const cell = document.createElement("button");
      cell.className = [
        "step-cell",
        state.pattern[track][step] ? "active" : "",
        getLengthClass(state.lengths[track][step])
      ].filter(Boolean).join(" ");
      cell.type = "button";
      cell.dataset.track = track;
      cell.dataset.step = step;
      cell.style.setProperty("--length-scale", state.lengths[track][step]);
      cell.title = `Track ${track + 1}, step ${step + 1}`;
      cell.addEventListener("mousedown", (event) => beginGridGesture(event, track, step));
      cell.addEventListener("mouseenter", (event) => continueGridGesture(event, track, step));
      cell.addEventListener("click", (event) => handleStepClick(event, track, step));
      cell.addEventListener("dragover", (event) => handleSampleDragOver(event, track, step, cell));
      cell.addEventListener("dragenter", (event) => {
        if (event.shiftKey) {
          previewSampleRange(track, step);
        }
      });
      cell.addEventListener("dragleave", () => {
        if (!state.dragPaint) {
          cell.classList.remove("drop-target");
        }
      });
      cell.addEventListener("drop", (event) => {
        event.preventDefault();
        cell.classList.remove("drop-target");
        const sampleId = event.dataTransfer.getData("text/plain");
        if (event.shiftKey) {
          paintSampleRange(sampleId, track, step);
        } else {
          placeSampleOnStep(sampleId, track, step);
        }
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
      <button class="mute-button${track.muted ? " muted" : ""}" type="button">M</button>
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
      setStatus(track.muted ? `Track ${index + 1} muted` : `Track ${index + 1} aan`);
    });
    elements.mixerList.appendChild(channel);
  });
}

function beginGridGesture(event, track, step) {
  if (event.ctrlKey || event.metaKey) {
    event.preventDefault();
    state.gridGesture = { mode: "steps", startStep: step, startCount: STEP_COUNT };
    state.skipNextClick = true;
    updateModifierStatus("Ctrl: sleep links/rechts voor minder/meer steps");
    return;
  }

  if (event.altKey) {
    event.preventDefault();
    state.gridGesture = { mode: "length", track, step, startStep: step, startLength: state.lengths[track][step] || 1 };
    state.pattern[track][step] = true;
    state.skipNextClick = true;
    updateModifierStatus("Alt: cel korter/langer maken");
    updateStepCell(track, step);
    return;
  }

  if (event.shiftKey) {
    event.preventDefault();
    state.gridGesture = { mode: "select", track, startStep: step };
    state.skipNextClick = true;
    paintStepRange(track, step);
    updateModifierStatus("Shift: horizontaal selecteren");
  }
}

function continueGridGesture(event, track, step) {
  if (!state.gridGesture || event.buttons !== 1) {
    return;
  }

  if (state.gridGesture.mode === "steps") {
    const delta = step - state.gridGesture.startStep;
    resizeSteps(state.gridGesture.startCount + delta, true);
    return;
  }

  if (state.gridGesture.mode === "length" && track === state.gridGesture.track) {
    const delta = step - state.gridGesture.startStep;
    const nextLength = clampNumber(state.gridGesture.startLength + delta * 0.5, 0.5, 4);
    state.lengths[track][state.gridGesture.step] = nextLength;
    updateStepCell(track, state.gridGesture.step);
    setStatus(`Lengte: ${nextLength}x`);
    return;
  }

  if (state.gridGesture.mode === "select" && track === state.gridGesture.track) {
    paintStepRange(track, step);
  }
}

function endGestures() {
  state.gridGesture = null;
  state.pianoGesture = null;
  updateModifierStatus();
}

function handleStepClick(event, track, step) {
  if (state.skipNextClick) {
    state.skipNextClick = false;
    return;
  }
  state.pattern[track][step] = !state.pattern[track][step];
  if (!state.pattern[track][step]) {
    state.lengths[track][step] = 1;
  }
  updateStepCell(track, step);
  renderTrackLabels();
  updateStudioOverview();
}

function paintStepRange(track, endStep) {
  const range = getRange(state.gridGesture.startStep, endStep);
  range.forEach((step) => {
    state.pattern[track][step] = true;
    updateStepCell(track, step);
  });
  renderTrackLabels();
  updateStudioOverview();
  setStatus(`${range.length} vakjes aangeduid op track ${track + 1}`);
}

function handleSampleDragOver(event, track, step, cell) {
  event.preventDefault();
  event.dataTransfer.dropEffect = "copy";
  if (event.shiftKey) {
    previewSampleRange(track, step);
    return;
  }
  clearRangePreview();
  cell.classList.add("drop-target");
}

function previewSampleRange(track, step) {
  if (!state.dragPaint || state.dragPaint.track !== track) {
    state.dragPaint = { track, startStep: step };
  }
  clearRangePreview();
  getRange(state.dragPaint.startStep, step).forEach((rangeStep) => {
    getCell(track, rangeStep)?.classList.add("range-preview");
  });
}

function paintSampleRange(sampleId, track, step) {
  const sample = getSampleById(sampleId);
  if (!sample) {
    return;
  }
  if (!state.dragPaint || state.dragPaint.track !== track) {
    state.dragPaint = { track, startStep: step };
  }
  state.tracks[track].sampleId = sampleId;
  state.selectedTrack = track;
  const range = getRange(state.dragPaint.startStep, step);
  range.forEach((rangeStep) => {
    state.pattern[track][rangeStep] = true;
  });
  state.dragPaint = null;
  clearRangePreview();
  renderTrackPicker();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  updateStudioOverview();
  setStatus(`${sample.name} geplaatst op ${range.length} vakjes`);
}

function placeSampleOnStep(sampleId, track, step) {
  const sample = getSampleById(sampleId);
  if (!sample) {
    return;
  }
  state.tracks[track].sampleId = sampleId;
  state.selectedTrack = track;
  state.pattern[track][step] = true;
  renderTrackPicker();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  updateStudioOverview();
  setStatus(`${sample.name} op track ${track + 1}, step ${step + 1}`);
}

function updateStepCell(track, step) {
  const cell = getCell(track, step);
  if (!cell) {
    return;
  }
  const playing = cell.classList.contains("playing");
  cell.className = [
    "step-cell",
    state.pattern[track][step] ? "active" : "",
    getLengthClass(state.lengths[track][step]),
    playing ? "playing" : ""
  ].filter(Boolean).join(" ");
  cell.style.setProperty("--length-scale", state.lengths[track][step]);
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
  updateStudioOverview();
  setStatus(`${sample.name} gekoppeld aan track ${trackIndex + 1}`);
}

function deleteSample(sampleId) {
  const sample = getSampleById(sampleId);
  if (!sample || !canDeleteSample(sample)) {
    return;
  }
  const fallback = state.samples.find((item) => item.source === "default");
  state.samples = state.samples.filter((item) => item.id !== sampleId);
  state.tracks.forEach((track) => {
    if (track.sampleId === sampleId) {
      track.sampleId = fallback.id;
    }
  });
  renderCategoryTabs();
  renderSampleList();
  renderTrackLabels();
  renderMixer();
  updateStudioOverview();
  setStatus(`${sample.name} verwijderd`);
}

async function addManualSound() {
  const type = elements.manualSoundType.value;
  const name = elements.manualSoundName.value.trim() || `${typeLabel(type)} Custom ${state.samples.length + 1}`;
  state.samples.push(createSynthSample(name, type, "custom", "manual"));
  elements.manualSoundName.value = "";
  elements.categoryFilter.value = type;
  renderCategoryTabs();
  renderSampleList();
  setStatus(`${name} toegevoegd`);
}

async function handleSampleUpload(event) {
  await ensureAudioContext();
  const files = Array.from(event.target.files);
  for (const file of files) {
    try {
      const buffer = await state.audioContext.decodeAudioData(await file.arrayBuffer());
      state.samples.push({
        id: `upload-${crypto.randomUUID()}`,
        name: file.name.replace(/\.[^/.]+$/, ""),
        path: file.name,
        type: "uploaded",
        tone: "audio",
        source: "uploaded",
        buffer,
        loadFailed: false
      });
    } catch (error) {
      setStatus(`${file.name} kon niet laden`);
    }
  }
  event.target.value = "";
  elements.categoryFilter.value = "uploaded";
  renderCategoryTabs();
  renderSampleList();
  setStatus(`${files.length} upload${files.length === 1 ? "" : "s"} toegevoegd`);
}

async function startRecording() {
  if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
    setStatus("Opnemen wordt niet ondersteund in deze browser");
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    state.recordedChunks = [];
    state.mediaRecorder = new MediaRecorder(stream);
    state.mediaRecorder.addEventListener("dataavailable", (event) => {
      if (event.data.size > 0) {
        state.recordedChunks.push(event.data);
      }
    });
    state.mediaRecorder.addEventListener("stop", () => finishRecording(stream));
    state.mediaRecorder.start();
    elements.recordButton.disabled = true;
    elements.stopRecordButton.disabled = false;
    elements.recordStatus.textContent = "Recording...";
    setStatus("Opname gestart");
  } catch (error) {
    setStatus("Microfoon toestemming geweigerd of niet beschikbaar");
  }
}

function stopRecording() {
  if (state.mediaRecorder?.state === "recording") {
    state.mediaRecorder.stop();
  }
}

async function finishRecording(stream) {
  stream.getTracks().forEach((track) => track.stop());
  elements.recordButton.disabled = false;
  elements.stopRecordButton.disabled = true;
  elements.recordStatus.textContent = "Opname opgeslagen in sounds";

  await ensureAudioContext();
  const type = elements.recordSoundType.value;
  const name = elements.recordSoundName.value.trim() || `Recording ${state.samples.length + 1}`;
  const blob = new Blob(state.recordedChunks, { type: state.recordedChunks[0]?.type || "audio/webm" });

  try {
    const buffer = await state.audioContext.decodeAudioData(await blob.arrayBuffer());
    state.samples.push({
      id: `record-${crypto.randomUUID()}`,
      name,
      path: "Browser recording",
      type,
      tone: "recording",
      source: "recording",
      buffer,
      loadFailed: false
    });
    elements.recordSoundName.value = "";
    elements.categoryFilter.value = type;
    renderCategoryTabs();
    renderSampleList();
    setStatus(`${name} opgenomen`);
  } catch (error) {
    setStatus("Opname kon niet als audio geladen worden");
  }
}

function renderPianoRoll() {
  const steps = clampPianoSteps();
  elements.pianoKeys.innerHTML = "";
  elements.pianoRollGrid.innerHTML = "";
  elements.pianoRollGrid.style.gridTemplateColumns = `repeat(${steps}, minmax(32px, 1fr))`;
  elements.pianoRollGrid.style.gridTemplateRows = `repeat(${pianoNotes.length}, 28px)`;

  pianoNotes.forEach((note) => {
    const key = document.createElement("button");
    key.className = `piano-key${note.includes("#") ? " sharp" : ""}`;
    key.type = "button";
    key.textContent = note;
    key.addEventListener("click", () => playPianoNote(note, 0.8, 0.45));
    elements.pianoKeys.appendChild(key);
  });

  pianoNotes.forEach((note, noteIndex) => {
    for (let step = 0; step < steps; step += 1) {
      const cell = document.createElement("button");
      const noteInfo = getPianoNoteAt(note, step);
      cell.className = `piano-cell${noteInfo ? " active" : ""}${noteInfo?.start === step ? " start" : ""}`;
      cell.type = "button";
      cell.dataset.note = note;
      cell.dataset.step = step;
      cell.textContent = noteInfo?.start === step ? note : "";
      cell.addEventListener("mousedown", (event) => beginPianoGesture(event, note, step));
      cell.addEventListener("mouseenter", (event) => continuePianoGesture(event, note, step));
      cell.addEventListener("click", (event) => {
        if (state.skipNextClick) {
          state.skipNextClick = false;
          return;
        }
        addOrRemovePianoNote(note, step, clampPianoLength());
      });
      elements.pianoRollGrid.appendChild(cell);
    }
  });
}

function beginPianoGesture(event, note, step) {
  event.preventDefault();
  state.pianoGesture = { note, start: step };
  state.skipNextClick = true;
  addOrUpdatePianoNote(note, step, clampPianoLength());
}

function continuePianoGesture(event, note, step) {
  if (!state.pianoGesture || event.buttons !== 1 || state.pianoGesture.note !== note) {
    return;
  }
  const length = Math.max(1, Math.abs(step - state.pianoGesture.start) + 1);
  const start = Math.min(step, state.pianoGesture.start);
  addOrUpdatePianoNote(note, start, length);
}

function addOrRemovePianoNote(note, step, length) {
  const existingIndex = state.pianoNotes.findIndex((item) => item.note === note && step >= item.start && step < item.start + item.length);
  if (existingIndex >= 0) {
    state.pianoNotes.splice(existingIndex, 1);
  } else {
    addOrUpdatePianoNote(note, step, length);
  }
  renderPianoRoll();
}

function addOrUpdatePianoNote(note, start, length) {
  const steps = clampPianoSteps();
  state.pianoNotes = state.pianoNotes.filter((item) => !(item.note === note && item.start === start));
  state.pianoNotes.push({ note, start, length: Math.min(length, steps - start), velocity: 0.86 });
  renderPianoRoll();
}

function getPianoNoteAt(note, step) {
  return state.pianoNotes.find((item) => item.note === note && step >= item.start && step < item.start + item.length);
}

function clearPianoPattern() {
  state.pianoNotes = [];
  renderPianoRoll();
  setStatus("Piano roll leeggemaakt");
}

async function playPianoPattern() {
  await ensureAudioContext();
  const stepMs = getStepDuration(0);
  state.pianoNotes.forEach((note) => {
    const start = state.audioContext.currentTime + (note.start * stepMs) / 1000;
    playPianoNote(note.note, note.velocity, (note.length * stepMs) / 1000, start);
  });
  setStatus("Piano beat speelt");
}

function addPianoSound() {
  if (!state.pianoNotes.length) {
    setStatus("Maak eerst minstens een piano noot");
    return;
  }
  const name = elements.pianoNameInput.value.trim() || `Piano loop ${state.samples.length + 1}`;
  state.samples.push({
    id: `piano-${crypto.randomUUID()}`,
    name,
    path: `${state.pianoNotes.length} notes`,
    type: "piano",
    tone: "roll",
    source: "piano",
    pianoNotes: state.pianoNotes.map((note) => ({ ...note })),
    pianoStepCount: clampPianoSteps(),
    buffer: null,
    loadFailed: false
  });
  elements.categoryFilter.value = "piano";
  renderCategoryTabs();
  renderSampleList();
  showStudioView();
  setStatus(`${name} toegevoegd aan Piano sounds`);
}

async function previewSample(sampleId) {
  await ensureAudioContext();
  const sample = getSampleById(sampleId);
  if (!sample) {
    return;
  }
  await playSample(sample, 0.9, 1, state.audioContext.currentTime);
  setStatus(`${sample.name} preview`);
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
  playCurrentStep();
  scheduleNextStep();
}

function scheduleNextStep() {
  clearTimeout(state.timerId);
  state.timerId = window.setTimeout(() => {
    state.currentStep = (state.currentStep + 1) % STEP_COUNT;
    playCurrentStep();
    scheduleNextStep();
  }, getStepDuration(state.currentStep));
}

function playCurrentStep() {
  updatePlaybackHighlight();
  state.tracks.forEach((track, trackIndex) => {
    if (!state.pattern[trackIndex][state.currentStep] || track.muted) {
      return;
    }
    const sample = getSampleById(track.sampleId);
    if (sample) {
      playSample(sample, track.volume, state.lengths[trackIndex][state.currentStep] || 1, state.audioContext.currentTime);
    }
  });
}

function stopPlayback() {
  clearTimeout(state.timerId);
  state.timerId = null;
  state.isPlaying = false;
  state.currentStep = 0;
  updatePlaybackHighlight();
  updateMasterMeter(0);
  setStatus("Gestopt");
}

async function playSample(sample, volume, lengthFactor, startTime) {
  if (sample.type === "piano" && sample.pianoNotes) {
    playPianoSample(sample, volume, startTime);
    return;
  }

  if (!sample.buffer && sample.path && !sample.loadFailed) {
    await loadSampleBuffer(sample);
  }

  if (sample.buffer) {
    const source = state.audioContext.createBufferSource();
    const gain = state.audioContext.createGain();
    source.buffer = sample.buffer;
    source.playbackRate.value = 1 / lengthFactor;
    gain.gain.value = volume;
    source.connect(gain);
    gain.connect(state.audioContext.destination);
    source.start(startTime);
    return;
  }

  playSynthSample(sample, volume, lengthFactor, startTime);
}

function playPianoSample(sample, volume, startTime) {
  const stepSeconds = getStepDuration(0) / 1000;
  sample.pianoNotes.forEach((note) => {
    playPianoNote(note.note, volume * note.velocity, note.length * stepSeconds, startTime + note.start * stepSeconds);
  });
}

function playPianoNote(note, volume = 0.8, duration = 0.5, startTime = null) {
  ensureAudioContext();
  const start = startTime ?? state.audioContext.currentTime;
  const frequency = noteToFrequency(note);
  const oscillator = state.audioContext.createOscillator();
  const gain = state.audioContext.createGain();
  const filter = state.audioContext.createBiquadFilter();
  oscillator.type = "triangle";
  oscillator.frequency.value = frequency;
  filter.type = "lowpass";
  filter.frequency.value = 2800;
  gain.gain.setValueAtTime(0.001, start);
  gain.gain.exponentialRampToValueAtTime(Math.max(0.001, volume), start + 0.018);
  gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
  oscillator.connect(filter);
  filter.connect(gain);
  gain.connect(state.audioContext.destination);
  oscillator.start(start);
  oscillator.stop(start + duration + 0.03);
}

function playSynthSample(sample, volume, lengthFactor, startTime) {
  const now = startTime;
  const oscillator = state.audioContext.createOscillator();
  const gain = state.audioContext.createGain();
  const filter = state.audioContext.createBiquadFilter();
  const type = sample.type;
  const tone = sample.tone || "classic";
  oscillator.connect(filter);
  filter.connect(gain);
  gain.connect(state.audioContext.destination);
  gain.gain.setValueAtTime(Math.max(0.001, volume), now);

  if (type === "kick") {
    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime(tone === "sub" ? 88 : tone === "punch" ? 165 : 132, now);
    oscillator.frequency.exponentialRampToValueAtTime(tone === "deep" || tone === "sub" ? 36 : 48, now + 0.18 * lengthFactor);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.26 * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + 0.28 * lengthFactor);
    return;
  }

  if (type === "snare" || type === "clap") {
    oscillator.type = tone === "bright" || tone === "wide" ? "square" : "triangle";
    oscillator.frequency.value = type === "clap" ? 1050 : tone === "body" ? 190 : 250;
    filter.type = "highpass";
    filter.frequency.value = tone === "body" ? 420 : 720;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.14 * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + 0.16 * lengthFactor);
    return;
  }

  if (type === "hat") {
    oscillator.type = "square";
    oscillator.frequency.value = tone === "open" ? 6400 : tone === "tick" ? 8800 : 7400;
    filter.type = "highpass";
    filter.frequency.value = tone === "open" ? 3800 : 5200;
    gain.gain.exponentialRampToValueAtTime(0.001, now + (tone === "open" ? 0.25 : 0.06) * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + (tone === "open" ? 0.26 : 0.07) * lengthFactor);
    return;
  }

  if (type === "fx") {
    oscillator.type = tone === "impact" ? "sine" : "sawtooth";
    oscillator.frequency.setValueAtTime(tone === "impact" ? 92 : 260, now);
    oscillator.frequency.exponentialRampToValueAtTime(tone === "impact" ? 42 : 1900, now + 0.35 * lengthFactor);
    filter.type = tone === "impact" ? "lowpass" : "bandpass";
    filter.frequency.value = tone === "impact" ? 560 : 850;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.38 * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + 0.4 * lengthFactor);
    return;
  }

  oscillator.type = type === "bass" ? "sawtooth" : "triangle";
  oscillator.frequency.value = type === "bass" ? (tone === "acid" ? 118 : tone === "sub" ? 54 : 82) : (tone === "pad" ? 260 : 380);
  filter.type = "lowpass";
  filter.frequency.value = type === "bass" ? (tone === "acid" ? 1100 : 420) : 1600;
  gain.gain.exponentialRampToValueAtTime(0.001, now + (type === "bass" ? 0.26 : 0.36) * lengthFactor);
  oscillator.start(now);
  oscillator.stop(now + (type === "bass" ? 0.28 : 0.38) * lengthFactor);
}

async function ensureAudioContext() {
  if (!state.audioContext) {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    state.audioContext = new AudioContextClass();
  }
}

async function preloadDefaultSamples() {
  await Promise.all(state.samples.filter((sample) => sample.path && !sample.buffer && !sample.loadFailed).map(loadSampleBuffer));
}

async function loadSampleBuffer(sample) {
  if (!sample.path || sample.source === "uploaded" || sample.source === "recording") {
    return;
  }
  try {
    const response = await fetch(sample.path);
    if (!response.ok) {
      throw new Error("Sample not found");
    }
    sample.buffer = await state.audioContext.decodeAudioData(await response.arrayBuffer());
  } catch (error) {
    sample.loadFailed = true;
  }
}

function fillStarterBeat() {
  resetGrids();
  state.tracks.forEach((track, trackIndex) => {
    const sample = getSampleById(track.sampleId);
    const type = sample?.type || "";
    let steps = [];
    if (type === "kick") steps = [0, 4, 8, 12];
    else if (type === "snare") steps = [4, 12];
    else if (type === "clap") steps = [6, 14];
    else if (type === "hat") steps = [2, 4, 6, 8, 10, 12, 14];
    else if (type === "bass") steps = [0, 3, 7, 10, 14];
    else if (type === "melody" || type === "piano") steps = [2, 6, 10, 15];
    else if (type === "fx") steps = [0, 15];
    steps.filter((step) => step < STEP_COUNT).forEach((step) => {
      state.pattern[trackIndex][step] = true;
      state.lengths[trackIndex][step] = type === "bass" || type === "piano" ? 1.5 : 1;
    });
  });
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus("Starter beat geplaatst");
}

function randomizeSelectedTrack() {
  const trackIndex = state.selectedTrack;
  const sample = getSampleById(state.tracks[trackIndex].sampleId);
  const density = clampDensity() / 100;
  state.pattern[trackIndex] = Array.from({ length: STEP_COUNT }, (_, step) => {
    const strong = step % 4 === 0;
    const offbeat = step % 2 === 1;
    let chance = density;
    if (sample?.type === "kick") chance = strong ? density + 0.28 : density * 0.25;
    else if (sample?.type === "snare" || sample?.type === "clap") chance = step === 4 || step === 12 ? density + 0.3 : density * 0.2;
    else if (sample?.type === "hat") chance = offbeat ? density + 0.25 : density * 0.7;
    else if (sample?.type === "fx") chance = step === 0 || step === STEP_COUNT - 1 ? density * 0.7 : density * 0.1;
    return Math.random() < Math.min(0.95, chance);
  });
  state.lengths[trackIndex] = state.pattern[trackIndex].map((active) => active && Math.random() > 0.78 ? 1.5 : 1);
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus(`Variatie gemaakt voor track ${trackIndex + 1}`);
}

function shiftSelectedTrack(direction) {
  state.pattern[state.selectedTrack] = rotateRow(state.pattern[state.selectedTrack], direction);
  state.lengths[state.selectedTrack] = rotateRow(state.lengths[state.selectedTrack], direction);
  renderStepGrid();
  renderTrackLabels();
  setStatus(`Track ${state.selectedTrack + 1} verschoven`);
}

function accentCurrentStep() {
  const track = state.selectedTrack;
  state.pattern[track][state.currentStep] = true;
  state.lengths[track][state.currentStep] = state.lengths[track][state.currentStep] >= 1.5 ? 1 : 1.5;
  updateStepCell(track, state.currentStep);
  updateStudioOverview();
  setStatus(`Accent/lengte aangepast op step ${state.currentStep + 1}`);
}

function clearSelectedTrack() {
  state.pattern[state.selectedTrack] = Array(STEP_COUNT).fill(false);
  state.lengths[state.selectedTrack] = Array(STEP_COUNT).fill(1);
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus(`Track ${state.selectedTrack + 1} leeggemaakt`);
}

function resetPattern() {
  resetGrids();
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus("Pattern leeggemaakt");
}

function normalizeMixer() {
  state.tracks.forEach((track) => {
    track.volume = 0.82;
    track.muted = false;
  });
  renderMixer();
  setStatus("Mixer genormaliseerd");
}

function unmuteAllTracks() {
  state.tracks.forEach((track) => {
    track.muted = false;
  });
  renderMixer();
  setStatus("Alle tracks staan aan");
}

function resizeSteps(nextCount, quiet = false) {
  const safeCount = Math.min(MAX_STEPS, Math.max(MIN_STEPS, nextCount));
  if (safeCount === STEP_COUNT) {
    return;
  }
  STEP_COUNT = safeCount;
  resizeRows(state.pattern, false);
  resizeRows(state.lengths, 1);
  state.currentStep = Math.min(state.currentStep, STEP_COUNT - 1);
  applyGridSizing();
  renderTimeline();
  renderStepHeader();
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  if (!quiet) {
    setStatus(`${STEP_COUNT} steps actief`);
  }
}

function resizeRows(grid, fillValue) {
  grid.forEach((row) => {
    while (row.length < STEP_COUNT) row.push(fillValue);
    if (row.length > STEP_COUNT) row.splice(STEP_COUNT);
  });
}

function applyGridSizing() {
  document.documentElement.style.setProperty("--step-count", STEP_COUNT);
  elements.stepCountMeta.textContent = `${STEP_COUNT} steps`;
}

function savePattern() {
  const data = {
    bpm: clampBpm(),
    swing: clampSwing(),
    density: clampDensity(),
    selectedTrack: state.selectedTrack,
    stepCount: STEP_COUNT,
    tracks: state.tracks,
    pattern: state.pattern,
    lengths: state.lengths,
    lightTheme: document.body.classList.contains("light-theme"),
    customSamples: state.samples.filter(canDeleteSample).map(serializeSample)
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
    restoreCustomSamples(data.customSamples);
    STEP_COUNT = Math.min(MAX_STEPS, Math.max(MIN_STEPS, Number(data.stepCount) || 16));
    elements.bpmInput.value = data.bpm || 120;
    elements.swingInput.value = data.swing || 0;
    elements.densityInput.value = data.density || 42;
    state.selectedTrack = Math.min(TRACK_COUNT - 1, data.selectedTrack || 0);
    state.pattern = normalizeBooleanGrid(data.pattern);
    state.lengths = normalizeNumberGrid(data.lengths, 1);
    state.tracks = normalizeTracks(data.tracks);
    document.body.classList.toggle("light-theme", Boolean(data.lightTheme));
    elements.themeButton.querySelector(".button-text").textContent = data.lightTheme ? "Dark" : "Light";
    applyGridSizing();
    renderEverything();
    setStatus("Pattern geladen");
  } catch (error) {
    setStatus("Opgeslagen pattern kon niet laden");
  }
}

function serializeSample(sample) {
  return {
    id: sample.id,
    name: sample.name,
    path: sample.path,
    type: sample.type,
    tone: sample.tone,
    source: sample.source,
    pianoNotes: sample.pianoNotes,
    pianoStepCount: sample.pianoStepCount
  };
}

function restoreCustomSamples(samples = []) {
  if (!Array.isArray(samples)) {
    return;
  }
  samples.forEach((sample) => {
    if (!sample?.id || state.samples.some((item) => item.id === sample.id)) {
      return;
    }
    state.samples.push({ ...sample, buffer: null, loadFailed: false });
  });
}

function normalizeTracks(tracks = []) {
  return Array.from({ length: TRACK_COUNT }, (_, index) => {
    const saved = tracks[index] || {};
    const sampleExists = state.samples.some((sample) => sample.id === saved.sampleId);
    return {
      id: index,
      sampleId: sampleExists ? saved.sampleId : state.tracks[index].sampleId,
      volume: Number.isFinite(saved.volume) ? saved.volume : 0.82,
      muted: Boolean(saved.muted)
    };
  });
}

function normalizeBooleanGrid(grid = []) {
  return Array.from({ length: TRACK_COUNT }, (_, track) => {
    const row = Array.isArray(grid[track]) ? grid[track] : [];
    return Array.from({ length: STEP_COUNT }, (_, step) => Boolean(row[step]));
  });
}

function normalizeNumberGrid(grid = [], fallback) {
  return Array.from({ length: TRACK_COUNT }, (_, track) => {
    const row = Array.isArray(grid[track]) ? grid[track] : [];
    return Array.from({ length: STEP_COUNT }, (_, step) => Number(row[step]) || fallback);
  });
}

function showStudioView() {
  document.querySelectorAll(".studio-view").forEach((view) => view.classList.remove("hidden"));
  elements.pianoPage.classList.add("hidden");
  elements.showStudioButton.classList.add("active-view-button");
  elements.showPianoButton.classList.remove("active-view-button");
  setStatus("Studio view");
}

function showPianoView() {
  document.querySelectorAll(".studio-view").forEach((view) => view.classList.add("hidden"));
  elements.pianoPage.classList.remove("hidden");
  elements.showStudioButton.classList.remove("active-view-button");
  elements.showPianoButton.classList.add("active-view-button");
  renderPianoRoll();
  setStatus("Piano Roll view");
}

function toggleTheme() {
  const light = document.body.classList.toggle("light-theme");
  elements.themeButton.querySelector(".button-text").textContent = light ? "Dark" : "Light";
  setStatus(light ? "Light palette actief" : "Dark palette actief");
}

function updatePlaybackHighlight() {
  elements.stepReadout.textContent = `Step ${String(state.currentStep + 1).padStart(2, "0")}`;
  document.querySelectorAll(".step-cell.playing").forEach((cell) => cell.classList.remove("playing"));
  document.querySelectorAll(".timeline-step.playing").forEach((step) => step.classList.remove("playing"));
  document.querySelectorAll(`.step-cell[data-step="${state.currentStep}"]`).forEach((cell) => cell.classList.add("playing"));
  elements.timelineSteps.querySelector(`[data-step="${state.currentStep}"]`)?.classList.add("playing");
  updateMasterMeter(calculateStepEnergy(state.currentStep));
}

function updateMasterMeter(energy) {
  const safeEnergy = Math.max(0, Math.min(1, energy));
  elements.masterMeter.querySelectorAll(".meter-bar").forEach((bar, index) => {
    const active = safeEnergy > index / 24;
    bar.classList.toggle("active", active);
    bar.style.height = active ? `${36 + ((index * 11) % 58)}%` : `${18 + ((index * 7) % 28)}%`;
  });
}

function calculateStepEnergy(step) {
  if (!state.isPlaying) return 0;
  const energy = state.tracks.reduce((total, track, index) => {
    return total + (state.pattern[index][step] && !track.muted ? track.volume : 0);
  }, 0);
  return Math.min(1, energy / 3.2);
}

function updateStudioOverview() {
  const sample = getSampleById(state.tracks[state.selectedTrack]?.sampleId);
  const activeSteps = countActiveSteps(state.selectedTrack);
  elements.selectedTrackName.textContent = `${state.selectedTrack + 1}. ${sample?.name || "Empty"}`;
  elements.activeStepCount.textContent = `${activeSteps} actieve step${activeSteps === 1 ? "" : "s"}`;
  updateModifierStatus();
}

function updateModifierStatus(message = null, keyEvent = null) {
  const active = [];
  if (message) active.push(message);
  else {
    if (keyEvent?.shiftKey) active.push("Shift: horizontaal aanduiden");
    if (keyEvent?.ctrlKey || keyEvent?.metaKey) active.push("Ctrl: steps wijzigen");
    if (keyEvent?.altKey) active.push("Alt: cel rekken");
  }
  elements.modifierStatus.textContent = active.length ? active.join(" + ") : "Geen modifier actief";
  elements.stepDepthValue.textContent = `${STEP_COUNT} steps`;
}

function clampBpm() {
  const bpm = clampNumber(Number(elements.bpmInput.value) || 120, 60, 220);
  elements.bpmInput.value = bpm;
  return bpm;
}

function clampSwing() {
  const swing = clampNumber(Number(elements.swingInput.value) || 0, 0, 65);
  elements.swingInput.value = swing;
  return swing;
}

function clampDensity() {
  const density = clampNumber(Number(elements.densityInput.value) || 42, 10, 90);
  elements.densityInput.value = density;
  elements.densityValue.textContent = `${density}%`;
  return density;
}

function updateDensityReadout() {
  clampDensity();
}

function getStepDuration(step = state.currentStep) {
  const bpm = clampBpm();
  const swing = clampSwing() / 100;
  const base = (60 / bpm / 4) * 1000;
  const offset = base * swing * 0.5;
  return step % 2 === 0 ? base + offset : base - offset;
}

function clampPianoSteps() {
  const steps = clampNumber(Number(elements.pianoStepsInput.value) || STEP_COUNT, MIN_STEPS, MAX_STEPS);
  elements.pianoStepsInput.value = steps;
  state.pianoNotes = state.pianoNotes.filter((note) => note.start < steps).map((note) => ({ ...note, length: Math.min(note.length, steps - note.start) }));
  return steps;
}

function clampPianoLength() {
  const length = clampNumber(Number(elements.pianoLengthInput.value) || 2, 1, 8);
  elements.pianoLengthInput.value = length;
  return length;
}

function getCell(track, step) {
  return elements.stepGrid.querySelector(`[data-track="${track}"][data-step="${step}"]`);
}

function getRange(a, b) {
  const min = Math.min(a, b);
  const max = Math.max(a, b);
  return Array.from({ length: max - min + 1 }, (_, index) => min + index);
}

function clearDropTargets() {
  document.querySelectorAll(".drop-target").forEach((element) => element.classList.remove("drop-target"));
  clearRangePreview();
}

function clearRangePreview() {
  document.querySelectorAll(".range-preview").forEach((element) => element.classList.remove("range-preview"));
}

function getLengthClass(length) {
  if (length >= 1.5) return "length-long";
  if (length <= 0.75) return "length-short";
  return "";
}

function rotateRow(row, direction) {
  return direction < 0 ? row.slice(1).concat(row[0]) : [row[row.length - 1]].concat(row.slice(0, -1));
}

function createSynthSample(name, type, tone, source) {
  return { id: `${source}-${crypto.randomUUID()}`, name, path: null, type, tone, source, buffer: null, loadFailed: false };
}

function getSampleById(id) {
  return state.samples.find((sample) => sample.id === id);
}

function getSampleSubtitle(sample) {
  if (sample.source === "piano") return `${sample.pianoNotes?.length || 0} piano notes`;
  if (sample.source === "recording") return "Recorded audio";
  if (sample.source === "uploaded") return sample.path || "Uploaded audio";
  if (sample.path) return sample.path;
  return `Synth ${sample.tone || "custom"}`;
}

function canDeleteSample(sample) {
  return ["manual", "uploaded", "recording", "piano"].includes(sample.source);
}

function countActiveSteps(track) {
  return state.pattern[track].filter(Boolean).length;
}

function typeLabel(type) {
  const labels = { all: "All", kick: "Kick", snare: "Snare", clap: "Clap", hat: "Hi-hat", bass: "Bass", melody: "Melody", fx: "FX", piano: "Piano", recording: "Recording", uploaded: "Upload", sound: "Sound" };
  return labels[type] || "Sound";
}

function noteToFrequency(note) {
  const noteNames = { C: 0, "C#": 1, D: 2, "D#": 3, E: 4, F: 5, "F#": 6, G: 7, "G#": 8, A: 9, "A#": 10, B: 11 };
  const match = note.match(/^([A-G]#?)(\d)$/);
  const semitone = noteNames[match[1]];
  const octave = Number(match[2]);
  const midi = (octave + 1) * 12 + semitone;
  return 440 * Math.pow(2, (midi - 69) / 12);
}

function clampNumber(value, min, max) {
  return Math.min(max, Math.max(min, value));
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
