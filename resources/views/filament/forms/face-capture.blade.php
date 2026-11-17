<div wire:ignore
    x-data="{
        state: $wire.entangle('{{ $getStatePath() }}').live,
        camOn: false, ok: false, msg: '', stream: null, loaded: false,
        MODELS: 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights',

        async lib() {
            if (window.faceapi) return;
            await new Promise((res, rej) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
                s.onload = res; s.onerror = rej;
                document.head.appendChild(s);
            });
        },
        async load() {
            if (this.loaded) return;
            await this.lib();
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(this.MODELS),
                faceapi.nets.faceLandmark68Net.loadFromUri(this.MODELS),
                faceapi.nets.faceRecognitionNet.loadFromUri(this.MODELS),
            ]);
            this.loaded = true;
        },
        async start() {
            try {
                this.ok = false;
                this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.camOn = true; this.msg = 'Mira de frente y pulsa Capturar.';
            } catch (e) { this.msg = 'No se pudo acceder a la cámara.'; }
        },
        stop() {
            if (this.stream) this.stream.getTracks().forEach(t => t.stop());
            this.stream = null; this.camOn = false;
        },
        async capture() {
            const v = this.$refs.video, c = document.createElement('canvas');
            c.width = v.videoWidth; c.height = v.videoHeight;
            c.getContext('2d').drawImage(v, 0, 0);
            this.stop(); this.msg = 'Analizando…';
            try {
                await this.load();
                let det = null;
                for (let i = 0; i < 3 && !det; i++) {
                    det = await faceapi.detectSingleFace(c, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
                        .withFaceLandmarks().withFaceDescriptor();
                }
                if (!det) { this.msg = 'No se detectó rostro. Intenta con más luz.'; return; }
                const photo = c.toDataURL('image/jpeg', 0.85);
                this.$refs.preview.src = photo; this.ok = true; this.msg = 'Rostro listo ✔';
                
                // Inyectar al estado enlazado
                this.state = JSON.stringify({ photo, descriptor: Array.from(det.descriptor) });

            } catch (e) { console.error(e); this.msg = 'Error al analizar. Mira la consola (F12).'; }
        },
    }" class="face-capture-container">

    <div class="media-box">
        <video x-ref="video" playsinline muted x-show="camOn" class="camera-stream"></video>
        <img x-ref="preview" x-show="ok" alt="Vista previa" class="preview-image">
    </div>

    <div class="controls-group">
        <button type="button" x-show="!camOn" x-on:click="start()" class="btn btn-primary">Abrir cámara</button>
        <button type="button" x-show="camOn" x-on:click="capture()" class="btn btn-success">Capturar</button>
        <button type="button" x-show="camOn" x-on:click="stop()" class="btn btn-danger">Cancelar</button>
    </div>

    <p x-text="msg" class="status-msg"></p>

    <style>
        .face-capture-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            max-width: 340px;
        }

        .dark .face-capture-container {
            background-color: #1e293b;
            border-color: #334155;
        }

        .media-box {
            width: 100%;
            min-height: 160px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #0f172a;
            border-radius: 8px;
            overflow: hidden;
        }

        .camera-stream,
        .preview-image {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 8px;
        }

        .controls-group {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .btn {
            padding: 8px 14px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
            color: #ffffff;
            flex: 1;
            text-align: center;
        }

        .btn-primary {
            background-color: #2563eb;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-success {
            background-color: #16a34a;
        }

        .btn-success:hover {
            background-color: #15803d;
        }

        .btn-danger {
            background-color: #dc2626;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .status-msg {
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
            margin: 0;
        }

        .dark .status-msg {
            color: #94a3b8;
        }
    </style>
</div>