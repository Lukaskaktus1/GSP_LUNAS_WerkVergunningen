const TRACK_COUNT = 8;
let STEP_COUNT = 16;
const STORAGE_KEY = "beatforge-pattern-v1";

const defaultSamples = [
  { name: "Kick Classic", path: "samples/kick.wav", type: "kick", tone: "classic" },
  { name: "Kick Deep", path: null, type: "kick", tone: "deep" },
  { name: "Kick Punch", path: null, type: "kick", tone: "punch" },
  { name: "Kick Sub", path: null, type: "kick", tone: "sub" },
  { name: "Snare Tight", path: "samples/snare.wav", type: "snare", tone: "tight" },
  { name: "Snare Dust", path: null, type: "snare", tone: "dust" },
  { name: "Snare Bright", path: null, type: "snare", tone: "bright" },
  { name: "Clap Studio", path: "samples/clap.wav", type: "clap", tone: "studio" },
  { name: "Clap Wide", path: null, type: "clap", tone: "wide" },
  { name: "Hi-hat Closed", path: "samples/hihat.wav", type: "hat", tone: "closed" },
  { name: "Hi-hat Open", path: null, type: "hat", tone: "open" },
  { name: "Hi-hat Tick", path: null, type: "hat", tone: "tick" },
  { name: "Bass Warm", path: "samples/bass.wav", type: "bass", tone: "warm" },
  { name: "Bass Acid", path: null, type: "bass", tone: "acid" },
  { name: "Bass Sub", path: null, type: "bass", tone: "sub" },
  { name: "Melody Pluck", path: "samples/melody.wav", type: "melody", tone: "pluck" },
  { name: "Melody Glass", path: null, type: "melody", tone: "glass" },
  { name: "Melody Pad", path: null, type: "melody", tone: "pad" },
  { name: "FX Riser", path: null, type: "fx", tone: "riser" },
  { name: "FX Zap", path: null, type: "fx", tone: "zap" }
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
  accents: [],
  velocities: [],
  lengths: [],
  dragPaint: null,
  editPaint: null,
  skipNextClick: false,
  modifierKeys: {
    shift: false,
    ctrl: false,
    alt: false
  }
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
  saveButton: document.getElementById("saveButton"),
  loadButton: document.getElementById("loadButton"),
  bpmInput: document.getElementById("bpmInput"),
  swingInput: document.getElementById("swingInput"),
  densityInput: document.getElementById("densityInput"),
  densityValue: document.getElementById("densityValue"),
  categoryFilter: document.getElementById("categoryFilter"),
  manualSoundName: document.getElementById("manualSoundName"),
  manualSoundType: document.getElementById("manualSoundType"),
  addSoundButton: document.getElementById("addSoundButton"),
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
  masterMeter: document.getElementById("masterMeter")
};

function initializeStudio() {
  state.samples = defaultSamples.map((sample, index) => ({
    ...sample,
    id: `default-${index}`,
    buffer: null,
    loadFailed: false
  }));

  const starterTypes = ["kick", "snare", "clap", "hat", "hat", "bass", "melody", "fx"];

  state.tracks = Array.from({ length: TRACK_COUNT }, (_, index) => {
    const sample = state.samples.find((item) => item.type === starterTypes[index]) || state.samples[index % state.samples.length];

    return {
      id: index,
      sampleId: sample.id,
      volume: 0.82,
      muted: false
    };
  });

  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.accents = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.velocities = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));
  state.lengths = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));

  applyGridSizing();
  renderMasterMeter();
  renderTimeline();
  renderStepHeader();
  renderTrackPicker();
  renderSampleList();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  bindEvents();
  updateDensityReadout();
  updateStudioOverview();
  updatePlaybackHighlight();
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
  elements.saveButton.addEventListener("click", savePattern);
  elements.loadButton.addEventListener("click", loadPattern);
  elements.bpmInput.addEventListener("change", clampBpm);
  elements.swingInput.addEventListener("change", clampSwing);
  elements.densityInput.addEventListener("input", updateDensityReadout);
  elements.categoryFilter.addEventListener("change", renderSampleList);
  elements.addSoundButton.addEventListener("click", addManualSound);
  elements.sampleUpload.addEventListener("change", handleSampleUpload);
  window.addEventListener("keydown", handleModifierKeys);
  window.addEventListener("keyup", handleModifierKeys);
  window.addEventListener("mouseup", () => {
    state.editPaint = null;
  });
}

function renderMasterMeter() {
  elements.masterMeter.innerHTML = "";

  for (let index = 0; index < 24; index += 1) {
    const bar = document.createElement("span");
    bar.className = "meter-bar";
    bar.style.height = `${22 + ((index * 17) % 55)}%`;
    elements.masterMeter.appendChild(bar);
  }
}

function renderTimeline() {
  elements.timelineSteps.innerHTML = "";
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
  const selectedCategory = elements.categoryFilter.value;
  const visibleSamples = selectedCategory === "all"
    ? state.samples
    : state.samples.filter((sample) => sample.type === selectedCategory);

  elements.sampleCount.textContent = visibleSamples.length;

  visibleSamples.forEach((sample) => {
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
      <span class="sample-path">${escapeHtml(sample.path || `Synth ${sample.tone || "custom"}`)}</span>
    `;
    details.appendChild(createWaveformElement(sample));

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
      state.dragPaint = null;
      setStatus(`${sample.name} wordt gesleept`);
    });

    item.addEventListener("dragend", () => {
      item.classList.remove("dragging");
      clearDropTargets();
      state.dragPaint = null;
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
    row.innerHTML = `
      <span class="track-number">${index + 1}</span>
      <span>
        <strong>${escapeHtml(sample?.name || "Empty")}</strong>
        <small>${countActiveSteps(index)} steps actief</small>
      </span>
      <span class="track-chip">${escapeHtml(sample?.type || "sound")}</span>
    `;
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
      cell.className = [
        "step-cell",
        state.pattern[track][step] ? "active" : "",
        state.accents[track][step] ? "accented" : "",
        getVelocityClass(state.velocities[track][step]),
        getLengthClass(state.lengths[track][step])
      ].filter(Boolean).join(" ");
      cell.type = "button";
      cell.dataset.track = track;
      cell.dataset.step = step;
      cell.title = `Track ${track + 1}, step ${step + 1} - Ctrl diepte, Alt lengte, Shift rij slepen`;
      cell.addEventListener("mousedown", (event) => beginStepEdit(event, track, step));
      cell.addEventListener("mouseenter", (event) => continueStepEdit(event, track, step));
      cell.addEventListener("click", (event) => handleStepClick(event, track, step));
      cell.addEventListener("dragover", (event) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = "copy";
        if (event.shiftKey) {
          previewShiftPaint(track, step);
        } else {
          clearRangePreview();
          cell.classList.add("drop-target");
        }
      });
      cell.addEventListener("dragenter", (event) => {
        if (event.shiftKey) {
          previewShiftPaint(track, step);
        }
      });
      cell.addEventListener("dragleave", () => {
        if (!state.dragPaint) {
          cell.classList.remove("drop-target");
        }
      });
      cell.addEventListener("drop", (event) => {
        event.preventDefault();
        const sampleId = event.dataTransfer.getData("text/plain");
        cell.classList.remove("drop-target");
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

function beginStepEdit(event, track, step) {
  if (!event.ctrlKey && !event.metaKey && !event.altKey) {
    return;
  }

  event.preventDefault();
  const mode = event.altKey ? "length" : "velocity";
  const direction = event.shiftKey ? -1 : 1;
  state.editPaint = {
    track,
    mode,
    direction
  };
  state.skipNextClick = true;
  applyStepEdit(mode, track, step, direction);
}

function continueStepEdit(event, track, step) {
  if (!state.editPaint || event.buttons !== 1 || state.editPaint.track !== track) {
    return;
  }

  applyStepEdit(state.editPaint.mode, track, step, state.editPaint.direction);
}

function applyStepEdit(mode, track, step, direction) {
  if (mode === "length") {
    changeStepLength(track, step, direction);
    return;
  }

  changeStepVelocity(track, step, direction);
}

function handleStepClick(event, track, step) {
  if (state.skipNextClick) {
    state.skipNextClick = false;
    return;
  }

  if (event.ctrlKey || event.metaKey) {
    changeStepVelocity(track, step, event.shiftKey ? -1 : 1);
    return;
  }

  if (event.altKey) {
    changeStepLength(track, step, event.shiftKey ? -1 : 1);
    return;
  }

  toggleStep(track, step);
}

function toggleStep(track, step) {
  state.pattern[track][step] = !state.pattern[track][step];
  if (!state.pattern[track][step]) {
    state.accents[track][step] = false;
    state.velocities[track][step] = 1;
    state.lengths[track][step] = 1;
  }
  const cell = elements.stepGrid.querySelector(`[data-track="${track}"][data-step="${step}"]`);
  cell.classList.toggle("active", state.pattern[track][step]);
  cell.classList.remove("accented");
  renderTrackLabels();
  updateStudioOverview();
}

function changeStepVelocity(track, step, direction) {
  state.pattern[track][step] = true;
  const values = [0.55, 0.8, 1, 1.25];
  const currentIndex = getClosestValueIndex(values, state.velocities[track][step]);
  const nextIndex = Math.min(values.length - 1, Math.max(0, currentIndex + direction));
  state.velocities[track][step] = values[nextIndex];
  state.accents[track][step] = state.velocities[track][step] > 1;
  updateStepCellVisual(track, step);
  renderTrackLabels();
  updatePlaybackHighlight();
  updateStudioOverview();
  setStatus(`Diepte step ${step + 1}: ${Math.round(state.velocities[track][step] * 100)}%`);
}

function changeStepLength(track, step, direction) {
  state.pattern[track][step] = true;
  const values = [0.5, 1, 1.5, 2];
  const currentIndex = getClosestValueIndex(values, state.lengths[track][step]);
  const nextIndex = Math.min(values.length - 1, Math.max(0, currentIndex + direction));
  state.lengths[track][step] = values[nextIndex];
  updateStepCellVisual(track, step);
  renderTrackLabels();
  updatePlaybackHighlight();
  updateStudioOverview();
  setStatus(`Lengte step ${step + 1}: ${state.lengths[track][step]}x`);
}

function updateStepCellVisual(track, step) {
  const cell = elements.stepGrid.querySelector(`[data-track="${track}"][data-step="${step}"]`);

  if (!cell) {
    return;
  }

  const isPlaying = cell.classList.contains("playing");
  cell.className = [
    "step-cell",
    state.pattern[track][step] ? "active" : "",
    state.accents[track][step] ? "accented" : "",
    getVelocityClass(state.velocities[track][step]),
    getLengthClass(state.lengths[track][step]),
    isPlaying ? "playing" : ""
  ].filter(Boolean).join(" ");
}

function getClosestValueIndex(values, currentValue) {
  return values.reduce((bestIndex, value, index) => {
    const bestDistance = Math.abs(values[bestIndex] - currentValue);
    const distance = Math.abs(value - currentValue);
    return distance < bestDistance ? index : bestIndex;
  }, 0);
}

function selectTrack(trackIndex) {
  state.selectedTrack = trackIndex;
  renderTrackPicker();
  renderTrackLabels();
  updateStudioOverview();
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
  state.velocities[trackIndex][stepIndex] = Math.max(state.velocities[trackIndex][stepIndex], 1);
  state.lengths[trackIndex][stepIndex] = state.lengths[trackIndex][stepIndex] || 1;

  renderTrackPicker();
  renderTrackLabels();
  renderMixer();

  const cell = elements.stepGrid.querySelector(`[data-track="${trackIndex}"][data-step="${stepIndex}"]`);
  cell?.classList.add("active");
  updateStudioOverview();
  setStatus(`${sample.name} op track ${trackIndex + 1}, step ${stepIndex + 1}`);
}

function previewShiftPaint(trackIndex, stepIndex) {
  if (!state.dragPaint || state.dragPaint.track !== trackIndex) {
    state.dragPaint = {
      track: trackIndex,
      startStep: stepIndex
    };
  }

  clearRangePreview();
  getStepRange(state.dragPaint.startStep, stepIndex).forEach((step) => {
    const cell = elements.stepGrid.querySelector(`[data-track="${trackIndex}"][data-step="${step}"]`);
    cell?.classList.add("range-preview");
  });
}

function paintSampleRange(sampleId, trackIndex, stepIndex) {
  const sample = getSampleById(sampleId);

  if (!sample) {
    return;
  }

  if (!state.dragPaint || state.dragPaint.track !== trackIndex) {
    state.dragPaint = {
      track: trackIndex,
      startStep: stepIndex
    };
  }

  const range = getStepRange(state.dragPaint.startStep, stepIndex);
  state.selectedTrack = trackIndex;
  state.tracks[trackIndex].sampleId = sampleId;

  range.forEach((step) => {
    state.pattern[trackIndex][step] = true;
    state.velocities[trackIndex][step] = Math.max(state.velocities[trackIndex][step], 1);
    state.lengths[trackIndex][step] = state.lengths[trackIndex][step] || 1;
  });

  state.dragPaint = null;
  clearRangePreview();
  renderTrackPicker();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  updateStudioOverview();
  setStatus(`${sample.name} geplaatst op ${range.length} steps in track ${trackIndex + 1}`);
}

function getStepRange(startStep, endStep) {
  const min = Math.min(startStep, endStep);
  const max = Math.max(startStep, endStep);
  return Array.from({ length: max - min + 1 }, (_, index) => min + index);
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

function createWaveformElement(sample) {
  const waveform = document.createElement("div");
  waveform.className = "waveform-strip";
  waveform.setAttribute("aria-hidden", "true");

  for (let index = 0; index < 18; index += 1) {
    const bar = document.createElement("span");
    const seed = sample.name.length + index * 9 + (sample.type || "").length * 5;
    bar.style.height = `${24 + (seed % 68)}%`;
    waveform.appendChild(bar);
  }

  return waveform;
}

function getVelocityClass(value) {
  if (value >= 1.18) {
    return "depth-hard";
  }

  if (value >= 0.9) {
    return "depth-mid";
  }

  if (value > 0) {
    return "depth-soft";
  }

  return "";
}

function getLengthClass(value) {
  if (value >= 1.5) {
    return "length-long";
  }

  if (value <= 0.5) {
    return "length-short";
  }

  return "";
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
    source.playbackRate.value = 1;
    gain.gain.value = 0.9;
    source.connect(gain);
    gain.connect(state.audioContext.destination);
    source.start();
  } else {
    playFallbackSound(sample, 0.9, 1);
  }

  setStatus(`${sample.name} preview`);
}

function clearDropTargets() {
  document.querySelectorAll(".drop-target").forEach((element) => element.classList.remove("drop-target"));
  clearRangePreview();
}

function clearRangePreview() {
  document.querySelectorAll(".range-preview").forEach((element) => element.classList.remove("range-preview"));
}

function fillStarterBeat() {
  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.accents = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.velocities = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));
  state.lengths = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));

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
      state.accents[trackIndex][step] = step % 4 === 0;
      state.velocities[trackIndex][step] = step % 4 === 0 ? 1.25 : 0.9;
      state.lengths[trackIndex][step] = type === "bass" || type === "melody" ? 1.5 : 1;
    });
  });

  renderStepGrid();
  renderTrackLabels();
  updatePlaybackHighlight();
  updateStudioOverview();
  setStatus("Starter beat geplaatst");
}

function clearSelectedTrack() {
  state.pattern[state.selectedTrack] = Array(STEP_COUNT).fill(false);
  state.accents[state.selectedTrack] = Array(STEP_COUNT).fill(false);
  state.velocities[state.selectedTrack] = Array(STEP_COUNT).fill(1);
  state.lengths[state.selectedTrack] = Array(STEP_COUNT).fill(1);
  renderStepGrid();
  renderTrackLabels();
  updatePlaybackHighlight();
  updateStudioOverview();
  setStatus(`Track ${state.selectedTrack + 1} leeggemaakt`);
}

function randomizeSelectedTrack() {
  const trackIndex = state.selectedTrack;
  const sample = getSampleById(state.tracks[trackIndex].sampleId);
  const density = clampDensity() / 100;
  const type = sample?.type || "";

  state.pattern[trackIndex] = Array.from({ length: STEP_COUNT }, (_, step) => {
    const strongStep = step % 4 === 0;
    const offbeat = step % 2 === 1;
    let chance = density;

    if (type === "kick") {
      chance = strongStep ? density + 0.28 : density * 0.35;
    } else if (type === "snare" || type === "clap") {
      chance = step === 4 || step === 12 ? density + 0.34 : density * 0.24;
    } else if (type === "hat") {
      chance = offbeat ? density + 0.25 : density * 0.7;
    } else if (type === "bass") {
      chance = strongStep ? density + 0.16 : density * 0.48;
    } else if (type === "melody") {
      chance = strongStep || step % 3 === 0 ? density * 0.78 : density * 0.28;
    } else if (type === "fx") {
      chance = step === 0 || step === STEP_COUNT - 1 ? density * 0.7 : density * 0.12;
    }

    return Math.random() < Math.min(0.92, chance);
  });

  state.accents[trackIndex] = state.pattern[trackIndex].map((active, step) => active && step % 4 === 0);
  state.velocities[trackIndex] = state.pattern[trackIndex].map((active, step) => {
    if (!active) {
      return 1;
    }

    return step % 4 === 0 ? 1.25 : 0.8 + Math.random() * 0.25;
  });
  state.lengths[trackIndex] = state.pattern[trackIndex].map((active) => {
    if (!active) {
      return 1;
    }

    return Math.random() > 0.78 ? 1.5 : 1;
  });
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus(`Variatie gemaakt voor track ${trackIndex + 1}`);
}

function shiftSelectedTrack(direction) {
  const trackIndex = state.selectedTrack;
  state.pattern[trackIndex] = rotateRow(state.pattern[trackIndex], direction);
  state.accents[trackIndex] = rotateRow(state.accents[trackIndex], direction);
  state.velocities[trackIndex] = rotateRow(state.velocities[trackIndex], direction);
  state.lengths[trackIndex] = rotateRow(state.lengths[trackIndex], direction);
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus(`Track ${trackIndex + 1} verschoven`);
}

function accentCurrentStep() {
  const trackIndex = state.selectedTrack;
  const stepIndex = state.currentStep;
  state.pattern[trackIndex][stepIndex] = true;
  state.accents[trackIndex][stepIndex] = !state.accents[trackIndex][stepIndex];
  state.velocities[trackIndex][stepIndex] = state.accents[trackIndex][stepIndex] ? 1.25 : 1;
  renderStepGrid();
  updatePlaybackHighlight();
  updateStudioOverview();
  setStatus(`Accent op track ${trackIndex + 1}, step ${stepIndex + 1}`);
}

function rotateRow(row, direction) {
  if (direction < 0) {
    return row.slice(1).concat(row[0]);
  }

  return [row[row.length - 1]].concat(row.slice(0, -1));
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
  }, getStepDuration(state.currentStep));
}

function playCurrentStep() {
  updatePlaybackHighlight();

  state.tracks.forEach((track, trackIndex) => {
    if (!state.pattern[trackIndex][state.currentStep] || track.muted) {
      return;
    }

    playTrackSample(track, trackIndex);
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

function updatePlaybackHighlight() {
  elements.stepReadout.textContent = `Step ${String(state.currentStep + 1).padStart(2, "0")}`;

  document.querySelectorAll(".step-cell.playing").forEach((cell) => cell.classList.remove("playing"));
  document.querySelectorAll(".timeline-step.playing").forEach((step) => step.classList.remove("playing"));

  document.querySelectorAll(`.step-cell[data-step="${state.currentStep}"]`).forEach((cell) => {
    cell.classList.add("playing");
  });

  const timelineStep = elements.timelineSteps.querySelector(`[data-step="${state.currentStep}"]`);
  timelineStep?.classList.add("playing");
  updateMasterMeter(calculateStepEnergy(state.currentStep));
}

async function playTrackSample(track, trackIndex) {
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
    const stepLength = state.lengths[trackIndex]?.[state.currentStep] || 1;

    source.buffer = sample.buffer;
    source.playbackRate.value = 1 / stepLength;
    gain.gain.value = getTrackStepVolume(track, trackIndex);
    source.connect(gain);
    gain.connect(state.audioContext.destination);
    source.start();
    return;
  }

  playFallbackSound(sample, getTrackStepVolume(track, trackIndex), state.lengths[trackIndex]?.[state.currentStep] || 1);
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

function playFallbackSound(sampleOrType, volume, lengthFactor = 1) {
  const type = typeof sampleOrType === "string" ? sampleOrType : sampleOrType.type;
  const tone = typeof sampleOrType === "string" ? "classic" : sampleOrType.tone || "classic";
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
    oscillator.frequency.setValueAtTime(tone === "sub" ? 95 : tone === "punch" ? 165 : 135, now);
    oscillator.frequency.exponentialRampToValueAtTime(tone === "deep" || tone === "sub" ? 34 : 48, now + 0.18 * lengthFactor);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.24 * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + 0.26 * lengthFactor);
    return;
  }

  if (type === "snare" || type === "clap") {
    oscillator.type = tone === "bright" || tone === "wide" ? "square" : "triangle";
    oscillator.frequency.value = type === "clap" ? (tone === "wide" ? 1200 : 900) : (tone === "dust" ? 180 : 260);
    filter.type = "highpass";
    filter.frequency.value = tone === "dust" ? 420 : 720;
    gain.gain.exponentialRampToValueAtTime(0.001, now + (type === "clap" ? 0.16 : 0.12) * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + 0.17 * lengthFactor);
    return;
  }

  if (type === "hat") {
    oscillator.type = "square";
    oscillator.frequency.value = tone === "open" ? 6400 : tone === "tick" ? 8800 : 7400;
    filter.type = "highpass";
    filter.frequency.value = tone === "open" ? 3800 : 5200;
    gain.gain.exponentialRampToValueAtTime(0.001, now + (tone === "open" ? 0.24 : 0.055) * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + (tone === "open" ? 0.25 : 0.06) * lengthFactor);
    return;
  }

  if (type === "fx") {
    oscillator.type = tone === "zap" ? "square" : "sawtooth";
    oscillator.frequency.setValueAtTime(tone === "zap" ? 1300 : 260, now);
    oscillator.frequency.exponentialRampToValueAtTime(tone === "zap" ? 120 : 1900, now + 0.35 * lengthFactor);
    filter.type = "bandpass";
    filter.frequency.value = tone === "zap" ? 1400 : 850;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.36 * lengthFactor);
    oscillator.start(now);
    oscillator.stop(now + 0.38 * lengthFactor);
    return;
  }

  oscillator.type = "sawtooth";
  oscillator.frequency.value = type === "bass" ? (tone === "acid" ? 118 : tone === "sub" ? 54 : 82) : (tone === "glass" ? 540 : tone === "pad" ? 260 : 330);
  filter.type = "lowpass";
  filter.frequency.value = type === "bass" ? (tone === "acid" ? 1100 : 420) : (tone === "pad" ? 900 : 1700);
  gain.gain.exponentialRampToValueAtTime(0.001, now + (type === "bass" ? 0.25 : 0.34) * lengthFactor);
  oscillator.start(now);
  oscillator.stop(now + (type === "bass" ? 0.26 : 0.36) * lengthFactor);
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
      setStatus(`${file.name} kon niet laden`);
    }
  }

  event.target.value = "";
  renderSampleList();
  updateStudioOverview();
  setStatus(`${files.length} sample${files.length === 1 ? "" : "s"} toegevoegd`);
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

function getStepDuration(stepIndex = state.currentStep) {
  const bpm = clampBpm();
  const swing = clampSwing() / 100;
  const baseDuration = (60 / bpm / 4) * 1000;
  const swingOffset = baseDuration * swing * 0.5;
  return stepIndex % 2 === 0 ? baseDuration + swingOffset : baseDuration - swingOffset;
}

function clampBpm() {
  const bpm = Math.min(220, Math.max(60, Number(elements.bpmInput.value) || 120));
  elements.bpmInput.value = bpm;
  return bpm;
}

function clampSwing() {
  const swing = Math.min(65, Math.max(0, Number(elements.swingInput.value) || 0));
  elements.swingInput.value = swing;
  return swing;
}

function clampDensity() {
  const density = Math.min(90, Math.max(10, Number(elements.densityInput.value) || 42));
  elements.densityInput.value = density;
  elements.densityValue.textContent = `${density}%`;
  return density;
}

function updateDensityReadout() {
  clampDensity();
}

function resetPattern() {
  state.pattern = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.accents = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(false));
  state.velocities = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));
  state.lengths = Array.from({ length: TRACK_COUNT }, () => Array(STEP_COUNT).fill(1));
  renderStepGrid();
  renderTrackLabels();
  updatePlaybackHighlight();
  updateStudioOverview();
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
    customSamples: state.samples
      .filter((sample) => sample.id.startsWith("manual-"))
      .map((sample) => ({
        id: sample.id,
        name: sample.name,
        type: sample.type,
        tone: sample.tone || "custom"
      })),
    pattern: state.pattern,
    accents: state.accents,
    velocities: state.velocities,
    lengths: state.lengths,
    stepCount: STEP_COUNT,
    swing: clampSwing(),
    density: clampDensity(),
    lightTheme: document.body.classList.contains("light-theme")
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
    elements.swingInput.value = data.swing || 0;
    elements.densityInput.value = data.density || 42;
    STEP_COUNT = Math.min(32, Math.max(8, Number(data.stepCount) || STEP_COUNT));
    restoreCustomSamples(data.customSamples);
    state.selectedTrack = data.selectedTrack || 0;
    state.pattern = normalizePattern(data.pattern);
    state.accents = normalizeAccents(data.accents, state.pattern);
    state.velocities = normalizeNumberGrid(data.velocities, 1, state.pattern);
    state.lengths = normalizeNumberGrid(data.lengths, 1, state.pattern);
    state.tracks = normalizeTracks(data.tracks);
    document.body.classList.toggle("light-theme", Boolean(data.lightTheme));
    elements.themeButton.querySelector(".button-text").textContent = data.lightTheme ? "Dark" : "Light";

    applyGridSizing();
    renderTimeline();
    renderStepHeader();
    renderSampleList();
    renderTrackPicker();
    renderTrackLabels();
    renderStepGrid();
    renderMixer();
    updateDensityReadout();
    updateStudioOverview();
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

function normalizeAccents(accents, pattern) {
  return Array.from({ length: TRACK_COUNT }, (_, trackIndex) => {
    const row = Array.isArray(accents?.[trackIndex]) ? accents[trackIndex] : [];
    return Array.from({ length: STEP_COUNT }, (_, stepIndex) => Boolean(row[stepIndex]) && Boolean(pattern[trackIndex][stepIndex]));
  });
}

function normalizeNumberGrid(grid, fallbackValue, pattern) {
  return Array.from({ length: TRACK_COUNT }, (_, trackIndex) => {
    const row = Array.isArray(grid?.[trackIndex]) ? grid[trackIndex] : [];
    return Array.from({ length: STEP_COUNT }, (_, stepIndex) => {
      const value = Number(row[stepIndex]);
      return pattern[trackIndex][stepIndex] && Number.isFinite(value) ? value : fallbackValue;
    });
  });
}

function resizeSteps(nextCount) {
  const safeCount = Math.min(32, Math.max(8, nextCount));

  if (safeCount === STEP_COUNT) {
    setStatus("Stappen blijven tussen 8 en 32");
    return;
  }

  STEP_COUNT = safeCount;
  resizeGridRows(state.pattern, false);
  resizeGridRows(state.accents, false);
  resizeGridRows(state.velocities, 1);
  resizeGridRows(state.lengths, 1);
  state.currentStep = Math.min(state.currentStep, STEP_COUNT - 1);

  applyGridSizing();
  renderTimeline();
  renderStepHeader();
  renderStepGrid();
  renderTrackLabels();
  updatePlaybackHighlight();
  updateStudioOverview();
  setStatus(`${STEP_COUNT} steps actief`);
}

function resizeGridRows(grid, fillValue) {
  grid.forEach((row) => {
    if (row.length > STEP_COUNT) {
      row.splice(STEP_COUNT);
      return;
    }

    while (row.length < STEP_COUNT) {
      row.push(fillValue);
    }
  });
}

function applyGridSizing() {
  document.documentElement.style.setProperty("--step-count", STEP_COUNT);
  elements.stepCountMeta.textContent = `${STEP_COUNT} steps`;
}

function addManualSound() {
  const type = elements.manualSoundType.value;
  const name = elements.manualSoundName.value.trim() || `${typeLabel(type)} Custom ${state.samples.length + 1}`;

  state.samples.push({
    id: `manual-${crypto.randomUUID()}`,
    name,
    path: null,
    type,
    tone: "custom",
    buffer: null,
    loadFailed: false
  });

  elements.manualSoundName.value = "";
  elements.categoryFilter.value = type;
  renderSampleList();
  setStatus(`${name} toegevoegd`);
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

function toggleTheme() {
  const light = document.body.classList.toggle("light-theme");
  elements.themeButton.querySelector(".button-text").textContent = light ? "Dark" : "Light";
  setStatus(light ? "Light palette actief" : "Dark palette actief");
}

function handleModifierKeys(event) {
  state.modifierKeys.shift = event.shiftKey;
  state.modifierKeys.ctrl = event.ctrlKey || event.metaKey;
  state.modifierKeys.alt = event.altKey;
  updateModifierStatus();
}

function updateModifierStatus() {
  const active = [];

  if (state.modifierKeys.shift) {
    active.push("Shift rij-sleep");
  }

  if (state.modifierKeys.ctrl) {
    active.push("Ctrl diepte");
  }

  if (state.modifierKeys.alt) {
    active.push("Alt lengte");
  }

  elements.modifierStatus.textContent = active.length ? active.join(" + ") : "Geen modifier actief";
  const selectedVelocity = state.velocities[state.selectedTrack]?.[state.currentStep] || 1;
  elements.stepDepthValue.textContent = `${Math.round(selectedVelocity * 100)}%`;
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

function restoreCustomSamples(customSamples) {
  if (!Array.isArray(customSamples)) {
    return;
  }

  customSamples.forEach((sample) => {
    if (!sample?.id || state.samples.some((item) => item.id === sample.id)) {
      return;
    }

    state.samples.push({
      id: sample.id,
      name: sample.name || "Custom sound",
      path: null,
      type: sample.type || "fx",
      tone: sample.tone || "custom",
      buffer: null,
      loadFailed: false
    });
  });
}

function getSampleById(sampleId) {
  return state.samples.find((sample) => sample.id === sampleId);
}

function countActiveSteps(trackIndex) {
  return state.pattern[trackIndex].filter(Boolean).length;
}

function updateStudioOverview() {
  const sample = getSampleById(state.tracks[state.selectedTrack]?.sampleId);
  const activeSteps = countActiveSteps(state.selectedTrack);
  elements.selectedTrackName.textContent = `${state.selectedTrack + 1}. ${sample?.name || "Empty"}`;
  elements.activeStepCount.textContent = `${activeSteps} actieve step${activeSteps === 1 ? "" : "s"}`;
  updateModifierStatus();
}

function updateMasterMeter(energy) {
  const safeEnergy = Math.max(0, Math.min(1, energy));
  elements.masterMeter.querySelectorAll(".meter-bar").forEach((bar, index) => {
    const threshold = index / 24;
    const active = safeEnergy > threshold;
    bar.classList.toggle("active", active);
    bar.style.height = active ? `${36 + ((index * 11) % 58)}%` : `${18 + ((index * 7) % 28)}%`;
  });
}

function calculateStepEnergy(stepIndex) {
  if (!state.isPlaying) {
    return 0;
  }

  const energy = state.tracks.reduce((total, track, trackIndex) => {
    if (!state.pattern[trackIndex][stepIndex] || track.muted) {
      return total;
    }

    return total + getTrackStepVolume(track, trackIndex);
  }, 0);

  return Math.min(1, energy / 3.4);
}

function getTrackStepVolume(track, trackIndex) {
  const accentBoost = state.accents[trackIndex]?.[state.currentStep] ? 1.22 : 1;
  const depth = state.velocities[trackIndex]?.[state.currentStep] || 1;
  return Math.min(1, track.volume * accentBoost * depth);
}

function typeLabel(type) {
  const labels = {
    kick: "Kick",
    snare: "Snare",
    clap: "Clap",
    hat: "Hi-hat",
    bass: "Bass",
    melody: "Melody",
    fx: "FX",
    uploaded: "Upload"
  };

  return labels[type] || "Sound";
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
