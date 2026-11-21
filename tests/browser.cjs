'use strict';

// Optional companion to integration.py; uses that suite's disposable database.
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require(process.env.SHELFIT_TEST_PLAYWRIGHT || 'playwright');

(async () => {
    const browser = await chromium.launch({
        ...(process.env.SHELFIT_TEST_BROWSER_BIN ? { executablePath: process.env.SHELFIT_TEST_BROWSER_BIN } : {}),
        headless: true,
    });
    try {
        for (const width of [1024, 320]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', (error) => errors.push(error.message));
            const fit = async () => assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            const routes = async () => {
                const actions = await page.locator('form[method="post"]').evaluateAll((forms) => forms.map((form) => new URL(form.getAttribute('action'), document.baseURI).pathname));
                assert(actions.length > 0);
                assert(actions.every((action) => action === '/staff/process.php'));
            };
            await page.goto(process.env.SHELFIT_TEST_BASE + '/');
            await fit();
            await page.getByRole('link', { name: 'Staff editor', exact: true }).click();
            await routes();
            await page.getByRole('textbox', { name: 'Username', exact: true }).fill('renamed');
            await page.getByLabel('Password', { exact: true }).fill('New-test-password-456');
            await page.getByRole('button', { name: 'Sign in', exact: true }).click();
            await page.getByRole('heading', { name: 'Manage studies' }).waitFor();
            await fit();
            await page.getByRole('link', { name: 'Add study', exact: true }).click();
            await routes();
            await page.getByLabel('Title', { exact: true }).fill('Browser test study');
            await page.getByLabel('Authors', { exact: true }).fill('Test Author');
            await page.getByLabel('Degree program', { exact: true }).fill('BSIT');
            await page.getByLabel('Academic year starts in').fill('2026');
            await page.locator('select[name=study_type]').selectOption('Capstone');
            await page.getByRole('button', { name: 'Save study', exact: true }).click();
            await page.getByRole('status').filter({ hasText: 'Study saved.' }).waitFor();
            if (process.env.SHELFIT_TEST_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.SHELFIT_TEST_SCREENSHOTS, `shelfit-study-${width}.png`), fullPage: true });
            }
            await fit();
            await routes();
            for (const name of ['Subjects', 'Shelf locations', 'Account']) {
                await page.getByRole('link', { name, exact: true }).click();
                await fit();
                await routes();
            }
            await page.getByRole('button', { name: 'Sign out', exact: true }).click();
            await page.getByRole('heading', { name: 'Staff sign in', exact: true }).waitFor();
            if (process.env.SHELFIT_TEST_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.SHELFIT_TEST_SCREENSHOTS, `shelfit-${width}.png`), fullPage: true });
            }
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('PASS: browser form submissions, shared processor URLs, navigation, and 1024px/320px layouts');
    } finally {
        await browser.close();
    }
})().catch((error) => { console.error(error); process.exitCode = 1; });
