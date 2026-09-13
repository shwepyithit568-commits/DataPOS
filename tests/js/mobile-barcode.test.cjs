const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/js/app-admin.js', 'utf8');
function fixture(globals = {}) {
    const start = source.indexOf('    /* ---- Camera Barcode Scanner ---- */');
    const end = source.indexOf('    /* ---- feedback ---- */', start);
    const methods = vm.runInNewContext('({' + source.slice(start, end) + '})', {
        setTimeout: fn => { fn(); }, console, navigator: {}, window: {},
        document: { getElementById: () => null }, ...globals,
    });
    return Object.assign(methods, { barcodeScannerOpen: true, _barcodeSession: 1,
        barcodeContinuous: true, barcodeCooldown: false, labels: {}, flash() {}, playBeep() {} });
}
test('scanned variant sends its parent and variant IDs without modal state', async () => {
    const app = fixture(); let payload;
    app.fetchJson = async path => { assert.match(path, /exact_code=1/); return { products: [{ id: 7, name: 'Phone', variants: [{ id: 8, sku: 'BLUE' }] }] }; };
    app.mutate = async (_, body) => { payload = body; return true; };
    await app.onBarcodeDetected('BLUE');
    assert.equal(payload.product_id, 7); assert.equal(payload.product_variant_id, 8);
    assert.equal(app.barcodeLastScanned, 'BLUE');
});
test('ambiguous results never add a guessed product', async () => {
    const app = fixture(); app.fetchJson = async () => ({ products: [{ id: 1 }, { id: 2 }] });
    app.mutate = async () => assert.fail('must not mutate'); await app.onBarcodeDetected('X');
});
test('closing during lookup discards stale results', async () => {
    const app = fixture(); app.fetchJson = async () => { app._barcodeSession++; return { products: [{ id: 1 }] }; };
    app.mutate = async () => assert.fail('stale request'); await app.onBarcodeDetected('X');
});
test('failed cart mutation never signals success', async () => {
    const app = fixture(); app.fetchJson = async () => ({ products: [{ id: 1 }] });
    app.mutate = async () => false; app.playBeep = () => assert.fail('false success');
    await app.onBarcodeDetected('X'); assert.equal(app.barcodeLastScanned, undefined);
});
test('parent barcode closes camera before displaying variant selection', async () => {
    const app = fixture(); app.fetchJson = async () => ({ products: [{ id: 1, variants: [{ id: 2, sku: 'BLUE' }] }] });
    app.closeBarcodeScanner = async () => { app.barcodeScannerOpen = false; };
    await app.onBarcodeDetected('PARENT'); assert.equal(app.barcodeScannerOpen, false); assert.equal(app.variantProduct.id, 1);
});
test('camera permission granted after closing releases the stream', async () => {
    let resolve, stopped = 0;
    const video = { srcObject: null, play: async () => assert.fail('closed camera must not play') };
    const app = fixture({ navigator: { mediaDevices: { getUserMedia: () => new Promise(r => { resolve = r; }) } }, document: { getElementById: () => video } });
    const opening = app.startCameraScanner(); await Promise.resolve();
    await app.closeBarcodeScanner(); resolve({ getTracks: () => [{ stop: () => stopped++ }] });
    await opening; assert.equal(stopped, 1); assert.equal(video.srcObject, null);
});
test('duplicate callbacks are locked for the full lookup', async () => {
    let resolve, calls = 0; const app = fixture();
    app.fetchJson = () => { calls++; return new Promise(r => { resolve = r; }); };
    const first = app.onBarcodeDetected('X'); await app.onBarcodeDetected('X');
    assert.equal(calls, 1); resolve({ products: [] }); await first;
});
test('failed native detector falls back to a serialized file decoder and removes its reader', async () => {
    let removed = 0, cleared = 0, reads = 0, hits = 0;
    const context = { drawImage() {} };
    const canvas = { getContext: () => context, toBlob: callback => callback({}) };
    const element = { style: {}, remove: () => removed++ };
    const app = fixture({
        BarcodeDetector: class { static async getSupportedFormats() { return ['code_128', 'ean_13']; } constructor() { throw new Error('unsupported'); } },
        File: class {},
        document: { createElement: tag => tag === 'canvas' ? canvas : element, body: { appendChild() {} } },
        window: { Html5Qrcode: class { async scanFile() { reads++; return 'ABC'; } clear() { cleared++; } } },
    });
    app._scanLoop = true;
    app.onBarcodeDetected = async () => { hits++; app._scanLoop = false; };
    await app._decodingLoop({ readyState: 2, paused: false, videoWidth: 1280, videoHeight: 720 }, 1);
    assert.equal(reads, 1); assert.equal(hits, 1); assert.equal(cleared, 1); assert.equal(removed, 1);
});
