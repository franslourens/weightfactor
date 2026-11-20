const nameInput = document.querySelector('#food-name');
const results = document.querySelector('#food-results');
const caloriesInput = document.querySelector('#food-calories');
const proteinInput = document.querySelector('#food-protein');
const carbsInput = document.querySelector('#food-carbs');
const fatInput = document.querySelector('#food-fat');
const serving = document.querySelector('#food-serving');

if (nameInput && results) {
    let timer = null;

    const hide = () => {
        results.hidden = true;
        results.replaceChildren();
    };

    const fill = (food) => {
        nameInput.value = food.name;
        caloriesInput.value = food.calories;
        proteinInput.value = food.protein;
        carbsInput.value = food.carbs;
        fatInput.value = food.fat;
        serving.textContent = food.serving ? `${food.serving} · ${food.source}` : food.source;
        hide();
    };

    const emptyNote = (query) => {
        if (!/^\d{8,14}$/.test(query)) {
            return 'No foods found. Try a simpler name, like oats or chicken.';
        }

        const digits = [...query].map(Number);
        const check = digits.pop();
        let total = 0;
        [...digits].reverse().forEach((digit, index) => {
            total += digit * (index % 2 === 0 ? 3 : 1);
        });
        const expected = (10 - (total % 10)) % 10;

        if (expected !== check) {
            return `That barcode doesn't check out. The last digit should be ${expected}.`;
        }

        return 'No product is listed for that barcode.';
    };

    nameInput.addEventListener('input', () => {
        clearTimeout(timer);
        const query = nameInput.value.trim();

        if (query.length < 2) {
            hide();
            return;
        }

        timer = setTimeout(async () => {
            const response = await fetch(`/foods/search?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                hide();
                return;
            }

            const foods = await response.json();
            results.replaceChildren();

            if (foods.length === 0) {
                const item = document.createElement('li');
                const note = document.createElement('small');
                note.textContent = emptyNote(query);
                item.append(note);
                results.append(item);
                results.hidden = false;
                return;
            }

            foods.forEach((food) => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';

                const title = document.createElement('span');
                title.textContent = food.name;

                const detail = document.createElement('small');
                detail.textContent = `${food.calories} kcal · P ${food.protein} g · C ${food.carbs} g · F ${food.fat} g · ${food.serving} · ${food.source}`;

                button.append(title, detail);
                button.addEventListener('click', () => fill(food));
                item.append(button);
                results.append(item);
            });

            results.hidden = foods.length === 0;
        }, 250);
    });

    document.addEventListener('click', (event) => {
        if (!nameInput.parentElement.contains(event.target)) {
            hide();
        }
    });

    const scanButton = document.querySelector('#scan-barcode');
    const dialog = document.querySelector('#barcode-dialog');
    const video = document.querySelector('#barcode-video');
    const status = document.querySelector('#barcode-status');
    const codeInput = document.querySelector('#barcode-code');
    const lookupButton = document.querySelector('#barcode-lookup');
    let stream = null;
    let scanTimer = null;
    let scanning = false;
    let scanGeneration = 0;
    const frame = document.createElement('canvas');
    const frameContext = frame.getContext('2d', { willReadFrequently: true });

    const useCode = (raw) => {
        const code = String(raw).replace(/\D/g, '');

        if (code.length < 8) {
            status.textContent = 'Enter the 8 to 14 digits printed under the bars.';
            codeInput?.focus();
            return;
        }

        nameInput.value = code;
        dialog.close();
        nameInput.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const stopScan = () => {
        scanGeneration += 1;
        clearInterval(scanTimer);
        scanTimer = null;
        scanning = false;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;

        if (video) {
            video.srcObject = null;
        }
    };

    const showManualEntry = (message) => {
        video.hidden = true;
        status.textContent = message;
        codeInput.focus();
    };

    const loadZxing = () => new Promise((resolve, reject) => {
        if (window.ZXingWASM?.readBarcodes) {
            resolve(window.ZXingWASM);
            return;
        }

        const script = document.createElement('script');
        script.src = '/js/zxing-wasm-reader.js';
        script.onload = () => {
            window.ZXingWASM.setZXingModuleOverrides({
                locateFile: (file, prefix) => (file.endsWith('.wasm') ? '/js/zxing_reader.wasm' : prefix + file),
            });
            resolve(window.ZXingWASM);
        };
        script.onerror = () => reject(new Error('zxing failed'));
        document.head.append(script);
    });

    const readFrames = async () => {
        const library = await loadZxing();
        const generation = scanGeneration;
        status.textContent = 'Hold the barcode steady in the frame.';

        const scan = async () => {
            if (generation !== scanGeneration || scanning) {
                return;
            }

            if (video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA || !video.videoWidth) {
                return;
            }

            scanning = true;

            try {
                const scale = Math.min(1, 960 / video.videoWidth);
                frame.width = Math.max(1, Math.round(video.videoWidth * scale));
                frame.height = Math.max(1, Math.round(video.videoHeight * scale));
                frameContext.drawImage(video, 0, 0, frame.width, frame.height);
                const codes = await library.readBarcodes(frameContext.getImageData(0, 0, frame.width, frame.height), {
                    formats: ['EAN13', 'EAN8', 'UPCA', 'UPCE', 'Code128'],
                    tryHarder: true,
                    tryRotate: true,
                    tryInvert: true,
                    maxNumberOfSymbols: 1,
                });
                const text = codes[0]?.text || '';

                if (generation === scanGeneration && text.replace(/\D/g, '').length >= 8) {
                    useCode(text);
                }
            } catch {
                if (generation === scanGeneration) {
                    status.textContent = 'Hold the barcode steady, or type the number.';
                }
            } finally {
                scanning = false;
            }
        };

        await scan();
        scanTimer = setInterval(scan, 250);
    };

    const openCamera = () => {
        if (!window.isSecureContext) {
            return Promise.reject(new Error('insecure'));
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            return Promise.reject(new Error('no camera'));
        }

        return navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        }).catch((error) => {
            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                throw error;
            }

            return navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false,
            });
        });
    };

    if (scanButton && dialog && video && status && codeInput) {
        scanButton.addEventListener('click', () => {
            hide();
            codeInput.value = '';
            video.playsInline = true;
            video.muted = true;
            status.textContent = 'Allow the camera to scan the barcode.';

            const requested = openCamera();
            dialog.showModal();

            requested.then(async (media) => {
                stream = media;
                video.hidden = false;
                video.srcObject = media;
                status.textContent = 'Point the camera at the barcode, or type the number.';

                try {
                    await video.play();
                } catch {
                    // The browser can start playback after the permission prompt.
                }

                try {
                    await readFrames();
                } catch {
                    status.textContent = 'The scanner could not start. Type the barcode number.';
                }
            }).catch(() => {
                const secure = window.isSecureContext
                    ? 'The camera stayed closed. Type the barcode number.'
                    : 'Open this site with https so the phone can use the camera. You can still type the number.';
                showManualEntry(secure);
            });
        });

        lookupButton?.addEventListener('click', () => useCode(codeInput.value));
        codeInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                useCode(codeInput.value);
            }
        });
        document.querySelector('#barcode-close')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => {
            stopScan();
            video.hidden = true;
        });
    }
}
