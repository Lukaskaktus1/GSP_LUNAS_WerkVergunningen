const TRACK_COUNT = 8;
const STORAGE_KEY = "beatforge-pattern-v2";
const ANALYTICS_CONSENT_KEY = "beatforge-analytics-consent";
const FAVORITES_KEY = "beatforge-favorite-samples";
const MIN_STEPS = 8;
const MAX_STEPS = 128;
const MIDI_PPQ = 480;
const MIDI_STEP_TICKS = MIDI_PPQ / 4;

let STEP_COUNT = 16;

const categoryConfig = [
  ["all", "Alles"],
  ["kick", "Kicks"],
  ["snare", "Snares"],
  ["clap", "Claps"],
  ["hat", "Hi-hats"],
  ["bass", "Bass"],
  ["melody", "Melody"],
  ["loop", "Loops"],
  ["fill", "Fills"],
  ["fx", "FX"],
  ["vocal", "Vocals"],
  ["piano", "Piano"],
  ["recording", "Recordings"],
  ["uploaded", "Uploads"],
  ["favorites", "Favorieten"]
];

const soundBlueprints = {
  kick: {
    count: 28,
    path: "samples/kick.wav",
    names: ["Classic", "Deep 808", "Punch", "Sub Clean", "Club", "Tape", "Short", "Hard", "Round", "Dark", "Air", "Knock", "Boom", "Drive"],
    tones: ["classic", "deep", "punch", "sub", "club", "tape", "short"]
  },
  snare: {
    count: 24,
    path: "samples/snare.wav",
    names: ["Tight", "Body", "Bright", "Rim", "Snap", "Tape", "Dry", "Wide", "Soft", "Crack", "Studio", "Dark"],
    tones: ["tight", "body", "bright", "rim", "snap", "tape"]
  },
  clap: {
    count: 18,
    path: "samples/clap.wav",
    names: ["Studio", "Wide", "Short", "Room", "Stack", "Tape", "Clean", "Bright", "Dry"],
    tones: ["studio", "wide", "short", "room", "stack", "bright"]
  },
  hat: {
    count: 30,
    path: "samples/hihat.wav",
    names: ["Closed", "Open", "Tick", "Air", "Dust", "Sharp", "Soft", "Tight", "Metal", "Clean", "Trap", "House", "Short", "Long", "Stereo"],
    tones: ["closed", "open", "tick", "air", "dust", "sharp", "soft"]
  },
  bass: {
    count: 28,
    path: "samples/bass.wav",
    names: ["Warm", "Acid", "Sub", "Rubber", "Dark", "Round", "Drive", "Pulse", "Short", "Long", "Mono", "Deep", "Clean", "Growl"],
    tones: ["warm", "acid", "sub", "rubber", "dark", "drive", "pulse"]
  },
  melody: {
    count: 32,
    path: "samples/melody.wav",
    names: ["Lead Pluck", "Lead Soft", "Pad Warm", "Bell", "Keys", "Dream", "Pulse", "Arp", "Chord", "Glow", "Analog", "Digital", "Soft Pluck", "Bright Pad", "LoFi", "Clean"],
    tones: ["pluck", "soft", "pad", "bell", "keys", "dream", "arp", "chord"]
  },
  fx: {
    count: 24,
    path: null,
    names: ["Riser", "Impact", "Sweep", "Drop", "Noise Hit", "Reverse", "Zap", "Downlift", "Laser", "Sub Drop", "Vinyl Stop", "Air Hit"],
    tones: ["riser", "impact", "sweep", "drop", "noise", "reverse", "zap", "downlift"]
  },
  piano: {
    count: 16,
    path: null,
    names: ["Piano Clean", "Piano Warm", "Piano Soft", "Piano Bright", "Piano Chord", "Piano Pluck", "Piano House", "Piano LoFi"],
    tones: ["clean", "warm", "soft", "bright", "chord", "pluck", "house", "lofi"]
  }
};

const defaultSamples = buildDefaultSamples();

function buildDefaultSamples() {
  return Object.entries(soundBlueprints).flatMap(([type, config]) => {
    return Array.from({ length: config.count }, (_, index) => {
      const name = config.names[index % config.names.length];
      const tone = config.tones[index % config.tones.length];
      const bank = Math.floor(index / config.names.length) + 1;
      const usesWav = Boolean(config.path) && index === 0;

      return {
        name: `${name} ${String(bank).padStart(2, "0")}`,
        path: usesWav ? config.path : null,
        type,
        tone,
        variant: index
      };
    });
  });
}

const pianoNotes = [
  "C6", "B5", "A#5", "A5", "G#5", "G5", "F#5", "F5", "E5", "D#5", "D5", "C#5",
  "C5", "B4", "A#4", "A4", "G#4", "G4", "F#4", "F4", "E4", "D#4", "D4", "C#4",
  "C4", "B3", "A#3", "A3", "G#3", "G3", "F#3", "F3", "E3", "D#3", "D3", "C#3", "C3"
];

const drumMidiNotes = {
  kick: 36,
  snare: 38,
  clap: 39,
  hat: 42,
  fx: 49,
  recording: 46,
  uploaded: 45
};

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
  pianoNotes: [],
  pianoVelocity: 104,
  favoriteSamples: new Set(),
  packSamplesLoaded: false,
  selectedPack: "all",
  sampleSearch: ""
};

const elements = {
  playButton: document.getElementById("playButton"),
  stopButton: document.getElementById("stopButton"),
  resetButton: document.getElementById("resetButton"),
  fillButton: document.getElementById("fillButton"),
  instantKitButton: document.getElementById("instantKitButton"),
  randomTrackButton: document.getElementById("randomTrackButton"),
  songModeButton: document.getElementById("songModeButton"),
  shiftLeftButton: document.getElementById("shiftLeftButton"),
  shiftRightButton: document.getElementById("shiftRightButton"),
  clearTrackButton: document.getElementById("clearTrackButton"),
  accentButton: document.getElementById("accentButton"),
  addStepButton: document.getElementById("addStepButton"),
  removeStepButton: document.getElementById("removeStepButton"),
  addBarButton: document.getElementById("addBarButton"),
  removeBarButton: document.getElementById("removeBarButton"),
  normalizeButton: document.getElementById("normalizeButton"),
  unmuteAllButton: document.getElementById("unmuteAllButton"),
  themeButton: document.getElementById("themeButton"),
  showStudioButton: document.getElementById("showStudioButton"),
  showPianoButton: document.getElementById("showPianoButton"),
  backToStudioButton: document.getElementById("backToStudioButton"),
  saveButton: document.getElementById("saveButton"),
  loadButton: document.getElementById("loadButton"),
  exportMidiButton: document.getElementById("exportMidiButton"),
  bpmInput: document.getElementById("bpmInput"),
  swingInput: document.getElementById("swingInput"),
  densityInput: document.getElementById("densityInput"),
  densityValue: document.getElementById("densityValue"),
  categoryFilter: document.getElementById("categoryFilter"),
  categoryTabs: document.getElementById("categoryTabs"),
  sampleSearchInput: document.getElementById("sampleSearchInput"),
  packFilter: document.getElementById("packFilter"),
  openSampleLibraryButton: document.getElementById("openSampleLibraryButton"),
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
  pianoVelocityInput: document.getElementById("pianoVelocityInput"),
  pianoVelocityValue: document.getElementById("pianoVelocityValue"),
  midiImportInput: document.getElementById("midiImportInput"),
  pianoKeys: document.getElementById("pianoKeys"),
  pianoRollGrid: document.getElementById("pianoRollGrid"),
  importMidiButton: document.getElementById("importMidiButton"),
  exportPianoMidiButton: document.getElementById("exportPianoMidiButton"),
  playPianoButton: document.getElementById("playPianoButton"),
  clearPianoButton: document.getElementById("clearPianoButton"),
  addPianoSoundButton: document.getElementById("addPianoSoundButton"),
  quantizePianoButton: document.getElementById("quantizePianoButton"),
  humanizePianoButton: document.getElementById("humanizePianoButton"),
  octaveDownButton: document.getElementById("octaveDownButton"),
  octaveUpButton: document.getElementById("octaveUpButton"),
  cookieBanner: document.getElementById("cookieBanner"),
  acceptCookiesButton: document.getElementById("acceptCookiesButton"),
  declineCookiesButton: document.getElementById("declineCookiesButton"),
  sampleLibraryModal: document.getElementById("sampleLibraryModal"),
  sampleLibraryGrid: document.getElementById("sampleLibraryGrid"),
  closeSampleLibraryButton: document.getElementById("closeSampleLibraryButton")
};

function initializeStudio() {
  state.favoriteSamples = loadFavoriteSamples();
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
  loadStickzSamples();
}

function bindEvents() {
  elements.playButton.addEventListener("click", startPlayback);
  elements.stopButton.addEventListener("click", stopPlayback);
  elements.resetButton.addEventListener("click", resetPattern);
  elements.fillButton.addEventListener("click", fillStarterBeat);
  elements.instantKitButton.addEventListener("click", buildInstantKit);
  elements.randomTrackButton.addEventListener("click", randomizeSelectedTrack);
  elements.songModeButton.addEventListener("click", buildSongLength);
  elements.shiftLeftButton.addEventListener("click", () => shiftSelectedTrack(-1));
  elements.shiftRightButton.addEventListener("click", () => shiftSelectedTrack(1));
  elements.clearTrackButton.addEventListener("click", clearSelectedTrack);
  elements.accentButton.addEventListener("click", accentCurrentStep);
  elements.addStepButton.addEventListener("click", () => resizeSteps(STEP_COUNT + 1));
  elements.removeStepButton.addEventListener("click", () => resizeSteps(STEP_COUNT - 1));
  elements.addBarButton.addEventListener("click", () => resizeSteps(STEP_COUNT + 8));
  elements.removeBarButton.addEventListener("click", () => resizeSteps(STEP_COUNT - 8));
  elements.normalizeButton.addEventListener("click", normalizeMixer);
  elements.unmuteAllButton.addEventListener("click", unmuteAllTracks);
  elements.themeButton.addEventListener("click", toggleTheme);
  elements.showStudioButton.addEventListener("click", showStudioView);
  elements.showPianoButton.addEventListener("click", showPianoView);
  elements.backToStudioButton.addEventListener("click", showStudioView);
  elements.saveButton.addEventListener("click", savePattern);
  elements.loadButton.addEventListener("click", loadPattern);
  elements.exportMidiButton.addEventListener("click", exportStudioMidi);
  elements.bpmInput.addEventListener("change", clampBpm);
  elements.swingInput.addEventListener("change", clampSwing);
  elements.densityInput.addEventListener("input", updateDensityReadout);
  elements.categoryFilter.addEventListener("change", () => {
    renderCategoryTabs();
    renderSampleList();
    renderSampleLibrary();
  });
  elements.sampleSearchInput.addEventListener("input", () => {
    state.sampleSearch = elements.sampleSearchInput.value.trim().toLowerCase();
    renderSampleList();
    renderSampleLibrary();
  });
  elements.packFilter.addEventListener("change", () => {
    state.selectedPack = elements.packFilter.value;
    renderCategoryTabs();
    renderSampleList();
    renderSampleLibrary();
  });
  elements.openSampleLibraryButton.addEventListener("click", openSampleLibrary);
  elements.closeSampleLibraryButton.addEventListener("click", closeSampleLibrary);
  elements.sampleLibraryModal.addEventListener("click", (event) => {
    if (event.target === elements.sampleLibraryModal) {
      closeSampleLibrary();
    }
  });
  elements.addSoundButton.addEventListener("click", addManualSound);
  elements.sampleUpload.addEventListener("change", handleSampleUpload);
  elements.recordButton.addEventListener("click", startRecording);
  elements.stopRecordButton.addEventListener("click", stopRecording);
  elements.pianoStepsInput.addEventListener("change", renderPianoRoll);
  elements.pianoLengthInput.addEventListener("change", clampPianoLength);
  elements.pianoVelocityInput.addEventListener("input", updatePianoVelocity);
  elements.importMidiButton.addEventListener("click", () => elements.midiImportInput.click());
  elements.midiImportInput.addEventListener("change", importMidiToPianoRoll);
  elements.exportPianoMidiButton.addEventListener("click", exportPianoRollMidi);
  elements.playPianoButton.addEventListener("click", playPianoPattern);
  elements.clearPianoButton.addEventListener("click", clearPianoPattern);
  elements.addPianoSoundButton.addEventListener("click", addPianoSound);
  elements.quantizePianoButton.addEventListener("click", quantizePianoRoll);
  elements.humanizePianoButton.addEventListener("click", humanizePianoRoll);
  elements.octaveDownButton.addEventListener("click", () => transposePianoRoll(-12));
  elements.octaveUpButton.addEventListener("click", () => transposePianoRoll(12));
  elements.acceptCookiesButton.addEventListener("click", acceptAnalyticsCookies);
  elements.declineCookiesButton.addEventListener("click", declineAnalyticsCookies);
  window.addEventListener("mouseup", endGestures);
  window.addEventListener("keydown", (event) => updateModifierStatus(null, event));
  window.addEventListener("keyup", (event) => updateModifierStatus(null, event));
}

function renderEverything() {
  renderMasterMeter();
  renderTimeline();
  renderStepHeader();
  renderCategoryTabs();
  renderPackFilter();
  renderTrackPicker();
  renderSampleList();
  renderSampleLibrary();
  renderTrackLabels();
  renderStepGrid();
  renderMixer();
  renderPianoRoll();
  updateDensityReadout();
  updatePianoVelocity();
  initializeCookieBanner();
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
    const count = getFilteredSamples({ category: value, ignoreSearch: true, ignorePack: true }).length;
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

function renderPackFilter() {
  const current = elements.packFilter.value || "all";
  const packs = Array.from(new Set(state.samples.map((sample) => sample.pack || sample.source || "Default"))).sort();
  elements.packFilter.innerHTML = '<option value="all">Alle packs</option>';
  packs.forEach((pack) => {
    const option = document.createElement("option");
    option.value = pack;
    option.textContent = pack === "default" ? "BeatForge synths" : pack;
    elements.packFilter.appendChild(option);
  });
  elements.packFilter.value = packs.includes(current) ? current : "all";
  state.selectedPack = elements.packFilter.value;
}

function renderSampleList() {
  elements.sampleList.innerHTML = "";
  const samples = getFilteredSamples();
  elements.sampleCount.textContent = samples.length;

  samples.slice(0, 90).forEach((sample) => {
    elements.sampleList.appendChild(createSampleListItem(sample));
  });

  if (samples.length > 90) {
    const more = document.createElement("li");
    more.className = "sample-item";
    more.innerHTML = `<span class="sample-path">${samples.length - 90} extra sounds in de volledige library</span>`;
    elements.sampleList.appendChild(more);
  }
}

function renderSampleLibrary() {
  if (!elements.sampleLibraryGrid) {
    return;
  }
  elements.sampleLibraryGrid.innerHTML = "";
  getFilteredSamples().forEach((sample) => {
    elements.sampleLibraryGrid.appendChild(createSampleListItem(sample));
  });
}

function createSampleListItem(sample) {
  const item = document.createElement("li");
  item.className = `sample-item${state.favoriteSamples.has(sample.id) ? " favorite" : ""}`;
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
    <div class="sample-tags">${getSampleTags(sample).map((tag) => `<span class="sample-tag">${escapeHtml(tag)}</span>`).join("")}</div>
  `;
  details.appendChild(createWaveformElement(sample));

  const actions = document.createElement("div");
  actions.className = "sample-actions";
  actions.append(createSampleButton("Play", "preview-button", () => previewSample(sample.id)));
  actions.append(createSampleButton("Gebruik", "", () => assignSampleToTrack(sample.id, state.selectedTrack)));
  actions.append(createSampleButton(state.favoriteSamples.has(sample.id) ? "Favoriet" : "Ster", `favorite-button${state.favoriteSamples.has(sample.id) ? " active" : ""}`, () => toggleFavoriteSample(sample.id)));

  if (canDeleteSample(sample)) {
    actions.append(createSampleButton("Verwijder", "delete-button", () => deleteSample(sample.id)));
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
  return item;
}

function getFilteredSamples(options = {}) {
  const category = options.category || elements.categoryFilter.value;
  const search = options.ignoreSearch ? "" : state.sampleSearch;
  const pack = options.ignorePack ? "all" : state.selectedPack;
  return state.samples.filter((sample) => {
    const categoryMatch = category === "all"
      || (category === "favorites" ? state.favoriteSamples.has(sample.id) : sample.type === category);
    const packMatch = pack === "all" || (sample.pack || sample.source || "Default") === pack;
    const haystack = `${sample.name} ${sample.type} ${sample.pack || ""} ${sample.bpm || ""} ${sample.key || ""} ${(sample.tags || []).join(" ")}`.toLowerCase();
    return categoryMatch && packMatch && (!search || haystack.includes(search));
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

async function loadStickzSamples() {
  try {
    const response = await fetch("samples/stickz-manifest.json");
    if (!response.ok) {
      throw new Error("Manifest missing");
    }
    const samples = await response.json();
    const existingIds = new Set(state.samples.map((sample) => sample.id));
    samples.forEach((sample, index) => {
      if (existingIds.has(sample.id)) {
        return;
      }
      state.samples.push({
        ...sample,
        id: sample.id || `stickz-${index}`,
        tone: sample.type,
        source: "stickz",
        buffer: null,
        loadFailed: false
      });
    });
    state.packSamplesLoaded = true;
    renderPackFilter();
    renderCategoryTabs();
    renderSampleList();
    renderSampleLibrary();
    if (!state.pattern.some((row) => row.some(Boolean))) {
      buildInstantKit(true);
    }
    setStatus(`${samples.length} echte Stickz samples geladen`);
  } catch (error) {
    setStatus("Stickz sample library kon niet geladen worden");
  }
}

function getSampleTags(sample) {
  return [
    sample.pack,
    sample.bpm ? `${sample.bpm} BPM` : "",
    sample.key ? `Key ${sample.key}` : "",
    sample.source === "stickz" ? "Stickz" : ""
  ].filter(Boolean).slice(0, 4);
}

function toggleFavoriteSample(sampleId) {
  if (state.favoriteSamples.has(sampleId)) {
    state.favoriteSamples.delete(sampleId);
  } else {
    state.favoriteSamples.add(sampleId);
  }
  saveFavoriteSamples();
  renderCategoryTabs();
  renderSampleList();
  renderSampleLibrary();
}

function loadFavoriteSamples() {
  try {
    return new Set(JSON.parse(localStorage.getItem(FAVORITES_KEY) || "[]"));
  } catch (error) {
    return new Set();
  }
}

function saveFavoriteSamples() {
  try {
    localStorage.setItem(FAVORITES_KEY, JSON.stringify(Array.from(state.favoriteSamples)));
  } catch (error) {
    setStatus("Favorieten konden niet opgeslagen worden");
  }
}

function openSampleLibrary() {
  renderSampleLibrary();
  elements.sampleLibraryModal.classList.remove("hidden");
}

function closeSampleLibrary() {
  elements.sampleLibraryModal.classList.add("hidden");
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
      const isStart = noteInfo?.start === step;
      cell.className = [
        "piano-cell",
        noteInfo ? "active" : "",
        isStart ? "start" : "",
        noteInfo && !isStart ? "continue" : ""
      ].filter(Boolean).join(" ");
      cell.type = "button";
      cell.dataset.note = note;
      cell.dataset.step = step;
      if (noteInfo) {
        cell.dataset.label = isStart ? note : "";
        cell.style.setProperty("--note-length", noteInfo.length);
        cell.style.setProperty("--note-velocity", noteInfo.velocity || 0.82);
        cell.title = `${note} - step ${noteInfo.start + 1}, lengte ${noteInfo.length}, velocity ${Math.round((noteInfo.velocity || 0.82) * 127)}`;
      } else {
        cell.title = `${note} - step ${step + 1}`;
      }
      cell.addEventListener("mousedown", (event) => beginPianoGesture(event, note, step));
      cell.addEventListener("mouseenter", (event) => continuePianoGesture(event, note, step));
      cell.addEventListener("contextmenu", (event) => {
        event.preventDefault();
        removePianoNoteAt(note, step);
      });
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
  if (event.button === 2) {
    state.pianoGesture = { note, mode: "erase" };
    removePianoNoteAt(note, step);
    state.skipNextClick = true;
    return;
  }
  state.pianoGesture = { note, start: step };
  state.skipNextClick = true;
  addOrUpdatePianoNote(note, step, clampPianoLength());
}

function continuePianoGesture(event, note, step) {
  if (!state.pianoGesture || state.pianoGesture.note !== note) {
    return;
  }
  if (state.pianoGesture.mode === "erase" && event.buttons === 2) {
    removePianoNoteAt(note, step);
    return;
  }
  if (event.buttons !== 1) {
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
  const safeLength = Math.max(1, Math.min(length, steps - start));
  const velocity = clampPianoVelocity() / 127;
  state.pianoNotes = state.pianoNotes.filter((item) => {
    if (item.note !== note) return true;
    const itemEnd = item.start + item.length;
    const nextEnd = start + safeLength;
    return itemEnd <= start || item.start >= nextEnd;
  });
  state.pianoNotes.push({ note, start, length: safeLength, velocity });
  state.pianoNotes.sort(sortPianoNotes);
  renderPianoRoll();
}

function getPianoNoteAt(note, step) {
  return state.pianoNotes.find((item) => item.note === note && step >= item.start && step < item.start + item.length);
}

function removePianoNoteAt(note, step) {
  const before = state.pianoNotes.length;
  state.pianoNotes = state.pianoNotes.filter((item) => !(item.note === note && step >= item.start && step < item.start + item.length));
  if (state.pianoNotes.length !== before) {
    renderPianoRoll();
    setStatus(`${note} verwijderd`);
  }
}

function clearPianoPattern() {
  state.pianoNotes = [];
  renderPianoRoll();
  setStatus("Piano roll leeggemaakt");
}

function quantizePianoRoll() {
  if (!state.pianoNotes.length) {
    setStatus("Geen piano notes om te quantizen");
    return;
  }
  const steps = clampPianoSteps();
  const merged = new Map();
  state.pianoNotes.forEach((note) => {
    const start = clampNumber(Math.round(note.start), 0, steps - 1);
    const length = clampNumber(Math.round(note.length) || 1, 1, steps - start);
    const key = `${note.note}-${start}`;
    const current = merged.get(key);
    if (!current || note.velocity > current.velocity) {
      merged.set(key, { ...note, start, length });
    }
  });
  state.pianoNotes = Array.from(merged.values()).sort(sortPianoNotes);
  renderPianoRoll();
  setStatus("Piano roll gequantized");
}

function humanizePianoRoll() {
  if (!state.pianoNotes.length) {
    setStatus("Geen piano notes om te humanizen");
    return;
  }
  state.pianoNotes = state.pianoNotes.map((note) => ({
    ...note,
    velocity: clampNumber((note.velocity || 0.82) + (Math.random() - 0.5) * 0.18, 0.25, 1)
  }));
  renderPianoRoll();
  setStatus("Piano roll velocity gehumanized");
}

function transposePianoRoll(semitones) {
  if (!state.pianoNotes.length) {
    setStatus("Geen piano notes om te verschuiven");
    return;
  }
  const transposed = state.pianoNotes
    .map((note) => ({ ...note, note: midiToNote(noteToMidi(note.note) + semitones) }))
    .filter((note) => pianoNotes.includes(note.note));
  if (!transposed.length) {
    setStatus("Octave shift valt buiten de piano roll");
    return;
  }
  state.pianoNotes = transposed.sort(sortPianoNotes);
  renderPianoRoll();
  setStatus(semitones > 0 ? "Piano roll octave omhoog" : "Piano roll octave omlaag");
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

function exportStudioMidi() {
  const eventsByTrack = state.tracks.map((track, trackIndex) => {
    const sample = getSampleById(track.sampleId);
    const events = [];
    if (!sample || track.muted) {
      return { name: `Track ${trackIndex + 1}`, events };
    }

    for (let step = 0; step < STEP_COUNT; step += 1) {
      if (!state.pattern[trackIndex][step]) {
        continue;
      }
      const stepTick = step * MIDI_STEP_TICKS;
      const lengthTicks = Math.max(1, Math.round((state.lengths[trackIndex][step] || 1) * MIDI_STEP_TICKS));
      if (sample.type === "piano" && sample.pianoNotes?.length) {
        sample.pianoNotes.forEach((note) => {
          addMidiNote(events, {
            tick: stepTick + Math.round(note.start * MIDI_STEP_TICKS),
            length: Math.max(1, Math.round(note.length * MIDI_STEP_TICKS)),
            note: noteToMidi(note.note),
            velocity: Math.round((note.velocity || 0.82) * track.volume * 127),
            channel: 0
          });
        });
      } else {
        addMidiNote(events, {
          tick: stepTick,
          length: lengthTicks,
          note: getMidiNoteForSample(sample, trackIndex),
          velocity: Math.round(track.volume * 112),
          channel: getMidiChannelForSample(sample, trackIndex)
        });
      }
    }
    return { name: sample.name || `Track ${trackIndex + 1}`, events };
  });

  const usedTracks = eventsByTrack.filter((track) => track.events.length);
  if (!usedTracks.length) {
    setStatus("Geen actieve steps om als MIDI te exporteren");
    return;
  }

  downloadMidiFile(`beatforge-${Date.now()}.mid`, [
    createTempoTrack(clampBpm()),
    ...usedTracks.map((track) => createMidiTrack(track.name, track.events))
  ]);
  setStatus("Studio pattern als MIDI geexporteerd");
}

function exportPianoRollMidi() {
  if (!state.pianoNotes.length) {
    setStatus("Maak eerst minstens een piano noot");
    return;
  }
  const events = [];
  state.pianoNotes.forEach((note) => {
    addMidiNote(events, {
      tick: Math.round(note.start * MIDI_STEP_TICKS),
      length: Math.max(1, Math.round(note.length * MIDI_STEP_TICKS)),
      note: noteToMidi(note.note),
      velocity: Math.round((note.velocity || 0.82) * 127),
      channel: 0
    });
  });
  const name = elements.pianoNameInput.value.trim() || "Piano loop";
  downloadMidiFile(`${sanitizeFileName(name)}.mid`, [
    createTempoTrack(clampBpm()),
    createMidiTrack(name, events)
  ]);
  setStatus("Piano roll als MIDI geexporteerd");
}

async function importMidiToPianoRoll(event) {
  const file = event.target.files?.[0];
  if (!file) {
    return;
  }
  try {
    const parsed = parseMidiFile(await file.arrayBuffer());
    const imported = parsed.notes
      .map((note) => {
        const start = Math.round(note.tick / MIDI_STEP_TICKS);
        const length = Math.max(1, Math.round(note.length / MIDI_STEP_TICKS));
        return {
          note: midiToNote(note.note),
          start,
          length,
          velocity: clampNumber(note.velocity / 127, 0.16, 1)
        };
      })
      .filter((note) => pianoNotes.includes(note.note) && note.start < MAX_STEPS)
      .map((note) => ({ ...note, length: Math.min(note.length, MAX_STEPS - note.start) }));

    if (!imported.length) {
      setStatus("Geen bruikbare MIDI notes gevonden voor deze piano roll");
      return;
    }

    const neededSteps = clampNumber(Math.max(...imported.map((note) => note.start + note.length)), MIN_STEPS, MAX_STEPS);
    elements.pianoStepsInput.value = neededSteps;
    state.pianoNotes = imported
      .filter((note) => note.start < neededSteps)
      .map((note) => ({ ...note, length: Math.min(note.length, neededSteps - note.start) }))
      .sort(sortPianoNotes);
    elements.pianoNameInput.value = file.name.replace(/\.[^/.]+$/, "") || "Imported MIDI";
    renderPianoRoll();
    setStatus(`${state.pianoNotes.length} MIDI notes geimporteerd`);
  } catch (error) {
    setStatus("MIDI bestand kon niet gelezen worden");
  } finally {
    event.target.value = "";
  }
}

function addMidiNote(events, { tick, length, note, velocity, channel }) {
  const safeVelocity = clampNumber(Math.round(velocity), 1, 127);
  const safeNote = clampNumber(Math.round(note), 0, 127);
  const safeChannel = clampNumber(Math.round(channel), 0, 15);
  events.push({ tick, bytes: [0x90 | safeChannel, safeNote, safeVelocity] });
  events.push({ tick: tick + Math.max(1, length), bytes: [0x80 | safeChannel, safeNote, 0] });
}

function createTempoTrack(bpm) {
  const tempo = Math.round(60000000 / bpm);
  return createMidiTrack("Tempo", [
    { tick: 0, bytes: [0xff, 0x51, 0x03, (tempo >> 16) & 0xff, (tempo >> 8) & 0xff, tempo & 0xff] },
    { tick: 0, bytes: [0xff, 0x58, 0x04, 0x04, 0x02, 0x18, 0x08] }
  ]);
}

function createMidiTrack(name, events) {
  const sorted = [
    { tick: 0, bytes: createTrackNameEvent(name) },
    ...events
  ].sort((a, b) => a.tick - b.tick || getMidiEventPriority(a.bytes) - getMidiEventPriority(b.bytes));
  const data = [];
  let lastTick = 0;
  sorted.forEach((event) => {
    writeVarLength(data, Math.max(0, event.tick - lastTick));
    data.push(...event.bytes);
    lastTick = event.tick;
  });
  writeVarLength(data, 0);
  data.push(0xff, 0x2f, 0x00);
  return createChunk("MTrk", data);
}

function createTrackNameEvent(name) {
  const bytes = Array.from(new TextEncoder().encode(name.slice(0, 48)));
  return [0xff, 0x03, bytes.length, ...bytes];
}

function downloadMidiFile(filename, trackChunks) {
  const header = createChunk("MThd", [0x00, 0x00, 0x00, 0x01, 0x00, trackChunks.length, (MIDI_PPQ >> 8) & 0xff, MIDI_PPQ & 0xff]);
  const blob = new Blob([new Uint8Array([...header, ...trackChunks.flat()])], { type: "audio/midi" });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  const objectUrl = link.href;
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
}

function createChunk(type, data) {
  const typeBytes = Array.from(type).map((char) => char.charCodeAt(0));
  const length = data.length;
  return [
    ...typeBytes,
    (length >> 24) & 0xff,
    (length >> 16) & 0xff,
    (length >> 8) & 0xff,
    length & 0xff,
    ...data
  ];
}

function writeVarLength(target, value) {
  let buffer = value & 0x7f;
  while ((value >>= 7)) {
    buffer <<= 8;
    buffer |= ((value & 0x7f) | 0x80);
  }
  while (true) {
    target.push(buffer & 0xff);
    if (buffer & 0x80) buffer >>= 8;
    else break;
  }
}

function parseMidiFile(arrayBuffer) {
  const view = new DataView(arrayBuffer);
  let offset = 0;
  const notes = [];

  const readText = (length) => {
    let value = "";
    for (let index = 0; index < length; index += 1) {
      value += String.fromCharCode(view.getUint8(offset + index));
    }
    offset += length;
    return value;
  };
  const readUint32 = () => {
    const value = view.getUint32(offset);
    offset += 4;
    return value;
  };
  const readUint16 = () => {
    const value = view.getUint16(offset);
    offset += 2;
    return value;
  };

  if (readText(4) !== "MThd") {
    throw new Error("Not a MIDI file");
  }
  const headerLength = readUint32();
  offset += 2;
  const trackCount = readUint16();
  const division = readUint16();
  offset += Math.max(0, headerLength - 6);
  const ticksPerQuarter = division > 0 ? division : MIDI_PPQ;

  for (let trackIndex = 0; trackIndex < trackCount && offset < view.byteLength; trackIndex += 1) {
    if (readText(4) !== "MTrk") {
      throw new Error("Invalid MIDI track");
    }
    const trackEnd = offset + readUint32();
    parseMidiTrack(view, offset, trackEnd, ticksPerQuarter, notes);
    offset = trackEnd;
  }
  return { notes, ticksPerQuarter };
}

function parseMidiTrack(view, startOffset, endOffset, ticksPerQuarter, notes) {
  let offset = startOffset;
  let tick = 0;
  let runningStatus = null;
  const activeNotes = new Map();
  const scaleTicks = MIDI_PPQ / ticksPerQuarter;

  const readVar = () => {
    let value = 0;
    let byte = 0;
    do {
      byte = view.getUint8(offset++);
      value = (value << 7) | (byte & 0x7f);
    } while (byte & 0x80);
    return value;
  };

  while (offset < endOffset) {
    tick += readVar();
    let status = view.getUint8(offset++);
    if (status < 0x80) {
      offset -= 1;
      status = runningStatus;
    } else if (status < 0xf0) {
      runningStatus = status;
    }

    if (status === 0xff) {
      offset += 1;
      const length = readVar();
      offset += length;
      continue;
    }
    if (status === 0xf0 || status === 0xf7) {
      const length = readVar();
      offset += length;
      continue;
    }

    const command = status & 0xf0;
    const channel = status & 0x0f;
    const note = view.getUint8(offset++);
    const velocity = command === 0xc0 || command === 0xd0 ? 0 : view.getUint8(offset++);

    if (command === 0x90 && velocity > 0) {
      activeNotes.set(`${channel}-${note}`, { tick, note, velocity, channel });
    } else if (command === 0x80 || command === 0x90) {
      const key = `${channel}-${note}`;
      const started = activeNotes.get(key);
      if (started) {
        notes.push({
          tick: Math.round(started.tick * scaleTicks),
          length: Math.max(1, Math.round((tick - started.tick) * scaleTicks)),
          note,
          velocity: started.velocity,
          channel
        });
        activeNotes.delete(key);
      }
    }
  }
}

function getMidiEventPriority(bytes) {
  if ((bytes[0] & 0xf0) === 0x80) return 0;
  if ((bytes[0] & 0xf0) === 0x90) return 1;
  return -1;
}

function getMidiNoteForSample(sample, trackIndex) {
  if (sample.type === "bass") return 36 + (trackIndex % 4) * 2;
  if (sample.type === "melody" || sample.type === "piano") return 60 + (trackIndex % 5) * 2;
  return drumMidiNotes[sample.type] || 48 + trackIndex;
}

function getMidiChannelForSample(sample, trackIndex) {
  if (["kick", "snare", "clap", "hat", "fx", "recording", "uploaded"].includes(sample.type)) {
    return 9;
  }
  return trackIndex % 8;
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
    source.playbackRate.value = sample.source === "stickz" ? 1 : 1 / lengthFactor;
    gain.gain.value = volume;
    source.connect(gain);
    gain.connect(state.audioContext.destination);
    source.start(startTime);
    if (sample.source === "stickz" && ["loop", "fill", "vocal"].includes(sample.type)) {
      const maxDuration = Math.max(0.08, (getStepDuration(0) / 1000) * lengthFactor);
      source.stop(startTime + Math.min(sample.buffer.duration, maxDuration));
    }
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
  const variant = Number(sample.variant) || 0;
  const pitchShift = 1 + ((variant % 9) - 4) * 0.018;
  const decayShift = 1 + (variant % 5) * 0.08;
  const filterShift = 1 + (variant % 7) * 0.055;
  oscillator.connect(filter);
  filter.connect(gain);
  gain.connect(state.audioContext.destination);
  gain.gain.setValueAtTime(Math.max(0.001, volume), now);

  if (type === "kick") {
    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime((tone === "sub" ? 88 : tone === "punch" ? 165 : tone === "short" ? 142 : 132) * pitchShift, now);
    oscillator.frequency.exponentialRampToValueAtTime((tone === "deep" || tone === "sub" ? 36 : 48) * pitchShift, now + 0.18 * lengthFactor);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.24 * lengthFactor * decayShift);
    oscillator.start(now);
    oscillator.stop(now + 0.3 * lengthFactor * decayShift);
    return;
  }

  if (type === "snare" || type === "clap") {
    oscillator.type = tone === "bright" || tone === "wide" ? "square" : "triangle";
    oscillator.frequency.value = (type === "clap" ? 1050 : tone === "body" ? 190 : 250) * pitchShift;
    filter.type = "highpass";
    filter.frequency.value = (tone === "body" ? 420 : 720) * filterShift;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.13 * lengthFactor * decayShift);
    oscillator.start(now);
    oscillator.stop(now + 0.17 * lengthFactor * decayShift);
    return;
  }

  if (type === "hat") {
    oscillator.type = "square";
    oscillator.frequency.value = (tone === "open" ? 6400 : tone === "tick" ? 8800 : tone === "soft" ? 5800 : 7400) * pitchShift;
    filter.type = "highpass";
    filter.frequency.value = (tone === "open" ? 3800 : 5200) * filterShift;
    gain.gain.exponentialRampToValueAtTime(0.001, now + (tone === "open" ? 0.25 : 0.055) * lengthFactor * decayShift);
    oscillator.start(now);
    oscillator.stop(now + (tone === "open" ? 0.27 : 0.075) * lengthFactor * decayShift);
    return;
  }

  if (type === "fx") {
    oscillator.type = tone === "impact" ? "sine" : "sawtooth";
    oscillator.frequency.setValueAtTime((tone === "impact" ? 92 : tone === "zap" ? 1260 : 260) * pitchShift, now);
    oscillator.frequency.exponentialRampToValueAtTime((tone === "impact" ? 42 : tone === "downlift" ? 180 : 1900) * pitchShift, now + 0.35 * lengthFactor);
    filter.type = tone === "impact" ? "lowpass" : "bandpass";
    filter.frequency.value = (tone === "impact" ? 560 : 850) * filterShift;
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.38 * lengthFactor * decayShift);
    oscillator.start(now);
    oscillator.stop(now + 0.42 * lengthFactor * decayShift);
    return;
  }

  oscillator.type = type === "bass" ? (tone === "sub" ? "sine" : "sawtooth") : (type === "piano" ? "triangle" : "triangle");
  oscillator.frequency.value = type === "bass"
    ? (tone === "acid" ? 118 : tone === "sub" ? 54 : 82) * pitchShift
    : (type === "piano" ? (tone === "bright" ? 520 : tone === "warm" ? 300 : 390) : tone === "pad" ? 260 : 380) * pitchShift;
  filter.type = "lowpass";
  filter.frequency.value = type === "bass" ? (tone === "acid" ? 1100 : 420) * filterShift : (type === "piano" ? 2200 : 1600) * filterShift;
  gain.gain.exponentialRampToValueAtTime(0.001, now + (type === "bass" ? 0.26 : type === "piano" ? 0.48 : 0.36) * lengthFactor * decayShift);
  oscillator.start(now);
  oscillator.stop(now + (type === "bass" ? 0.28 : type === "piano" ? 0.5 : 0.38) * lengthFactor * decayShift);
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
    else if (type === "melody" || type === "piano" || type === "loop") steps = [0, 8];
    else if (type === "fill") steps = [STEP_COUNT - 4];
    else if (type === "fx" || type === "vocal") steps = [0, Math.max(0, STEP_COUNT - 1)];
    for (let offset = 0; offset < STEP_COUNT; offset += 16) {
      steps.map((step) => step + offset).filter((step) => step < STEP_COUNT).forEach((step) => {
        state.pattern[trackIndex][step] = true;
        state.lengths[trackIndex][step] = type === "bass" || type === "piano" || type === "loop" ? 2 : 1;
      });
    }
  });
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
  setStatus("Starter beat geplaatst");
}

function buildInstantKit(quiet = false) {
  const starterTypes = ["kick", "snare", "clap", "hat", "hat", "bass", "loop", "fx"];
  starterTypes.forEach((type, index) => {
    const pool = state.samples.filter((sample) => sample.type === type && sample.source === "stickz");
    const fallback = state.samples.filter((sample) => sample.type === type);
    const sample = randomFrom(pool.length ? pool : fallback);
    if (sample) {
      state.tracks[index].sampleId = sample.id;
      state.tracks[index].volume = type === "loop" ? 0.65 : 0.82;
      state.tracks[index].muted = false;
    }
  });
  fillStarterBeat();
  renderTrackPicker();
  renderMixer();
  if (!quiet) {
    setStatus("Nieuwe kit met echte samples geladen");
  }
}

function buildSongLength() {
  resizeSteps(64, true);
  buildInstantKit(true);
  addSongEnergy();
  setStatus("Song canvas van 64 steps gemaakt");
}

function addSongEnergy() {
  state.tracks.forEach((track, trackIndex) => {
    const sample = getSampleById(track.sampleId);
    const type = sample?.type || "";
    for (let step = 0; step < STEP_COUNT; step += 1) {
      const bar = Math.floor(step / 16);
      if (bar === 0 && (type === "clap" || type === "fx")) {
        state.pattern[trackIndex][step] = false;
      }
      if (bar >= 2 && type === "hat" && step % 2 === 0) {
        state.pattern[trackIndex][step] = true;
      }
      if (bar === 3 && type === "fill" && step >= STEP_COUNT - 8) {
        state.pattern[trackIndex][step] = step % 2 === 0;
      }
    }
  });
  renderStepGrid();
  renderTrackLabels();
  updateStudioOverview();
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
    elements.themeButton.querySelector(".button-text").textContent = data.lightTheme ? "Donker" : "Licht";
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
  setStatus("Studio actief");
}

function showPianoView() {
  document.querySelectorAll(".studio-view").forEach((view) => view.classList.add("hidden"));
  elements.pianoPage.classList.remove("hidden");
  elements.showStudioButton.classList.remove("active-view-button");
  elements.showPianoButton.classList.add("active-view-button");
  renderPianoRoll();
  setStatus("Piano Roll actief");
}

function initializeCookieBanner() {
  const consent = getStoredAnalyticsConsent();
  if (consent === "granted") {
    enableAnalytics();
    elements.cookieBanner.classList.add("hidden");
    return;
  }
  if (consent === "denied") {
    disableAnalytics();
    elements.cookieBanner.classList.add("hidden");
    return;
  }
  disableAnalytics();
  elements.cookieBanner.classList.remove("hidden");
}

function acceptAnalyticsCookies() {
  storeAnalyticsConsent("granted");
  enableAnalytics();
  elements.cookieBanner.classList.add("hidden");
  setStatus("Analytics cookies geaccepteerd");
}

function declineAnalyticsCookies() {
  storeAnalyticsConsent("denied");
  disableAnalytics();
  elements.cookieBanner.classList.add("hidden");
  setStatus("Analytics cookies geweigerd");
}

function enableAnalytics() {
  if (typeof window.gtag !== "function") {
    return;
  }
  window.gtag("consent", "update", { analytics_storage: "granted" });
  if (window.beatForgeAnalyticsConfigured) {
    return;
  }
  window.gtag("config", "G-KRSDK8CF6V");
  window.gtag("config", "G-PZ9WG0Q900");
  window.beatForgeAnalyticsConfigured = true;
}

function disableAnalytics() {
  if (typeof window.gtag !== "function") {
    return;
  }
  window.gtag("consent", "update", {
    analytics_storage: "denied",
    ad_storage: "denied",
    ad_user_data: "denied",
    ad_personalization: "denied"
  });
}

function getStoredAnalyticsConsent() {
  try {
    return localStorage.getItem(ANALYTICS_CONSENT_KEY);
  } catch (error) {
    return null;
  }
}

function storeAnalyticsConsent(value) {
  try {
    localStorage.setItem(ANALYTICS_CONSENT_KEY, value);
  } catch (error) {
    setStatus("Cookiekeuze kon niet opgeslagen worden");
  }
}

function toggleTheme() {
  const light = document.body.classList.toggle("light-theme");
  elements.themeButton.querySelector(".button-text").textContent = light ? "Donker" : "Licht";
  setStatus(light ? "Licht thema actief" : "Donker thema actief");
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

function clampPianoVelocity() {
  const velocity = clampNumber(Number(elements.pianoVelocityInput.value) || 104, 20, 127);
  elements.pianoVelocityInput.value = velocity;
  elements.pianoVelocityValue.textContent = velocity;
  state.pianoVelocity = velocity;
  return velocity;
}

function updatePianoVelocity() {
  clampPianoVelocity();
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

function randomFrom(items) {
  if (!items.length) {
    return null;
  }
  return items[Math.floor(Math.random() * items.length)];
}

function createSynthSample(name, type, tone, source) {
  return { id: `${source}-${crypto.randomUUID()}`, name, path: null, type, tone, source, buffer: null, loadFailed: false };
}

function getSampleById(id) {
  return state.samples.find((sample) => sample.id === id);
}

function getSampleSubtitle(sample) {
  if (sample.source === "stickz") {
    return `${sample.pack || "Stickz"}${sample.bpm ? ` - ${sample.bpm} BPM` : ""}${sample.key ? ` - ${sample.key}` : ""}`;
  }
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
  const labels = { all: "Alles", kick: "Kick", snare: "Snare", clap: "Clap", hat: "Hi-hat", bass: "Bass", melody: "Melody", loop: "Loop", fill: "Fill", fx: "FX", vocal: "Vocal", piano: "Piano", recording: "Recording", uploaded: "Upload", favorites: "Favoriet", sound: "Sound" };
  return labels[type] || "Sound";
}

function noteToFrequency(note) {
  return 440 * Math.pow(2, (noteToMidi(note) - 69) / 12);
}

function noteToMidi(note) {
  const noteNames = { C: 0, "C#": 1, D: 2, "D#": 3, E: 4, F: 5, "F#": 6, G: 7, "G#": 8, A: 9, "A#": 10, B: 11 };
  const match = note.match(/^([A-G]#?)(\d)$/);
  if (!match) {
    return 60;
  }
  const semitone = noteNames[match[1]];
  const octave = Number(match[2]);
  return (octave + 1) * 12 + semitone;
}

function midiToNote(midi) {
  const names = ["C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"];
  const safeMidi = clampNumber(Math.round(midi), 0, 127);
  return `${names[safeMidi % 12]}${Math.floor(safeMidi / 12) - 1}`;
}

function sortPianoNotes(a, b) {
  return a.start - b.start || pianoNotes.indexOf(a.note) - pianoNotes.indexOf(b.note);
}

function sanitizeFileName(value) {
  const clean = value.trim().replace(/[\\/:*?"<>|]+/g, "-").replace(/\s+/g, "-").toLowerCase();
  return clean || "beatforge-midi";
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
