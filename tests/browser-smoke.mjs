import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { setTimeout as delay } from 'node:timers/promises';

const origin = 'http://127.0.0.1:8719';
const tab = await fetch('http://127.0.0.1:8720/json/new?about:blank', { method: 'PUT' });
assert.ok(tab.ok, 'Start a headless Chrome instance on loopback port 8720.');
const page = await tab.json();
const socket = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
  socket.addEventListener('open', resolve, { once: true });
  socket.addEventListener('error', reject, { once: true });
});

let sequence = 0;
const pending = new Map();
socket.addEventListener('message', event => {
  const message = JSON.parse(event.data);
  const waiter = pending.get(message.id);
  if (!waiter) return;
  pending.delete(message.id);
  if (message.error) waiter.reject(new Error(message.error.message));
  else waiter.resolve(message.result);
});

function command(method, params = {}) {
  return new Promise((resolve, reject) => {
    const id = ++sequence;
    const timer = setTimeout(() => {
      pending.delete(id);
      reject(new Error(`Chrome did not respond to ${method}.`));
    }, 12000);
    pending.set(id, {
      resolve: result => { clearTimeout(timer); resolve(result); },
      reject: error => { clearTimeout(timer); reject(error); }
    });
    socket.send(JSON.stringify({ id, method, params }));
  });
}

async function evaluate(expression) {
  const result = await command('Runtime.evaluate', { expression, returnByValue: true });
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || 'Page evaluation failed.');
  return result.result.value;
}

async function waitFor(expression) {
  for (let attempt = 0; attempt < 50; attempt += 1) {
    if (await evaluate(expression)) return;
    await delay(150);
  }
  throw new Error(`Browser check timed out: ${expression}`);
}

async function navigate(path) {
  const target = origin + path;
  await command('Page.navigate', { url: target });
  await waitFor(`location.href === ${JSON.stringify(target)} && document.readyState === 'complete'`);
}

async function screenshot(file) {
  const { data } = await command('Page.captureScreenshot', { format: 'png' });
  await fs.writeFile(`dist/qa/${file}`, Buffer.from(data, 'base64'));
}

try {
  await fs.mkdir('dist/qa', { recursive: true });
  await command('Page.enable');
  await command('Network.clearBrowserCookies');
  await command('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  for (const [label, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
    await command('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
    await navigate('/');
    assert.equal(await evaluate('document.querySelector("h1").textContent.trim()'), 'Illuminate Your Walls with Art');
    assert.ok(await evaluate('document.documentElement.scrollWidth <= innerWidth'), `No ${label} horizontal overflow.`);
    if (label === 'mobile') {
      assert.ok(await evaluate('(() => { const button = document.querySelector("[data-menu-toggle]"); button.click(); const open = button.getAttribute("aria-expanded") === "true"; button.click(); return open; })()'));
    }
    await screenshot(`home-${label}.png`);
  }

  await navigate('/shop/');
  assert.ok(await evaluate('!!document.querySelector(".woocommerce-ordering, .collection-filter")'));
  await navigate('/product-category/islamic-wall-art/');
  assert.ok(await evaluate('document.body.innerText.includes("QA Designer Fixture")'));
  await navigate('/product/qa-designer-fixture/');
  const designer = 'document.querySelector("[data-nspd]")';
  await waitFor(`!!${designer}`);
  await evaluate(`(() => {
    for (const field of document.querySelectorAll('.variations select')) {
      field.selectedIndex = 1;
      field.dispatchEvent(new Event('change', { bubbles: true }));
    }
    const option = document.querySelector('[data-nspd-text-option]');
    option.value = 'نور';
    option.dispatchEvent(new Event('input', { bubbles: true }));
  })()`);
  await waitFor('document.querySelector("[data-preview-text]").textContent === "نور"');
  await waitFor('document.querySelector("[data-nspd-estimate]").textContent.includes("1,350")');
  assert.ok(await evaluate('document.documentElement.scrollWidth <= innerWidth'), 'No product-page horizontal overflow.');
  await evaluate(`${designer}.scrollIntoView()`);
  await delay(250);
  await screenshot('designer-mobile.png');
  await evaluate('setTimeout(() => document.querySelector(".single_add_to_cart_button").click(), 0); true');
  await waitFor('document.querySelector(".woocommerce-message")?.textContent.includes("cart")');
  await navigate('/cart/');
  await waitFor('document.body.innerText.includes("QA Designer Fixture")');
  assert.ok(await evaluate('document.body.innerText.includes("نور")'));
  await navigate('/checkout/');
  await waitFor('document.body.innerText.includes("QA Designer Fixture")');
  console.log('Desktop and mobile layouts, menu, collection, product variation, SVG text, estimate, cart metadata and checkout rendering passed.');
} finally {
  socket.close();
  await fetch(`http://127.0.0.1:8720/json/close/${page.id}`).catch(() => {});
}
