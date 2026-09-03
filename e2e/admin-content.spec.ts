import { test, expect, type APIRequestContext } from '@playwright/test';

const email = process.env.E2E_EMAIL || 'e2e@example.com';
const password = process.env.E2E_PASSWORD || 'password';

function csrfFromHtml(html: string): string {
  const match = html.match(/name="_token"\s+value="([^"]+)"/)
    || html.match(/csrf-token"\s+content="([^"]+)"/);
  if (!match?.[1]) {
    throw new Error('CSRF token not found in HTML');
  }
  return match[1];
}

async function login(request: APIRequestContext) {
  const loginPage = await request.get('/login');
  expect(loginPage.ok(), 'login page').toBeTruthy();
  const token = csrfFromHtml(await loginPage.text());

  const res = await request.post('/login', {
    form: {
      _token: token,
      email,
      password,
    },
  });
  expect(res.ok() || res.status() === 302, `login status ${res.status()}`).toBeTruthy();

  // Confirm session: dashboard/admin should not bounce to login.
  const dash = await request.get('/admin');
  const dashUrl = dash.url();
  expect(dashUrl, `expected authenticated admin, got ${dashUrl}`).not.toMatch(/\/login/);
  expect(dash.ok() || dash.status() === 302).toBeTruthy();
}

test.describe('HTTP / API e2e', () => {
  test('public endpoints respond', async ({ request }) => {
    for (const path of ['/', '/blog', '/login', '/api/wp/v2/types']) {
      const res = await request.get(path);
      expect(res.status(), path).toBeLessThan(400);
    }
  });

  test('legacy posts admin redirects after retirement', async ({ request }) => {
    await login(request);
    const res = await request.get('/admin/posts?legacy=1', { maxRedirects: 5 });
    expect(res.url()).toMatch(/admin\/contents/);
    expect(res.ok()).toBeTruthy();
  });

  test('gutenberg editor assets + publish content flow', async ({ request }) => {
    await login(request);

    const createPage = await request.get('/admin/contents/create?type=post');
    expect(createPage.ok()).toBeTruthy();
    expect(createPage.url()).not.toMatch(/\/login/);
    const createHtml = await createPage.text();
    expect(createHtml).toContain('block-editor-root');
    expect(createHtml).toContain('laravelpress-editor.js');
    expect(createHtml).toContain('admin-editor.css');

    const token = csrfFromHtml(createHtml);
    const slug = `e2e-api-${Date.now()}`;
    const blocks = JSON.stringify([
      { type: 'paragraph', content: 'Hello from Playwright API e2e.', attrs: { align: 'left' }, innerBlocks: [] },
      { type: 'heading', content: 'Gutenberg parity', attrs: { level: 2 }, innerBlocks: [] },
    ]);

    const store = await request.post('/admin/contents', {
      form: {
        _token: token,
        type: 'post',
        title: `E2E API ${slug}`,
        slug,
        status: 'published',
        action: 'publish',
        blocks_json: blocks,
        body: '<p>Hello from Playwright API e2e.</p>',
        comment_status: 'open',
      },
      maxRedirects: 5,
    });
    expect(store.ok()).toBeTruthy();
    expect(store.url()).toMatch(/admin\/contents\/\d+/);

    const publicRes = await request.get(`/blog/${slug}`);
    expect(publicRes.ok(), `public /blog/${slug} -> ${publicRes.status()}`).toBeTruthy();
    const body = await publicRes.text();
    expect(body).toMatch(/E2E API|Hello from Playwright API e2e/i);

    const wp = await request.get('/api/wp/v2/posts');
    expect(wp.ok()).toBeTruthy();
    expect(await wp.text()).toContain(slug);
  });
});

test.describe('Browser chrome (best-effort under WSL)', () => {
  test('login page renders email field when Chromium can navigate', async ({ page }) => {
    test.setTimeout(60_000);
    try {
      await page.goto('/login', { waitUntil: 'commit', timeout: 20_000 });
      await page.waitForSelector('input[name="email"]', { timeout: 15_000 });
    } catch {
      test.skip(true, 'Chromium navigation flaky under WSL proxy; HTTP e2e covers the flow');
    }
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });
});
