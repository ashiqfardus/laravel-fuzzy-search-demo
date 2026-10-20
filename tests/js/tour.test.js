// The guided tour (resources/js/tour.js): each card opens the page it names, and on a stop's
// own page the card docks beside it and moves on to the next stop. Run: npm test
import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { fuzzyTour } from '../../resources/js/tour.js';

let opened;
let store;

beforeEach(() => {
    opened = [];
    store = new Map();
    globalThis.sessionStorage = {
        getItem: (k) => store.get(k) ?? null,
        setItem: (k, v) => store.set(k, String(v)),
        removeItem: (k) => store.delete(k),
    };
    globalThis.window = { location: { pathname: '/', assign: (href) => opened.push(href) } };
});

/** A fresh page load at path, as the layout's x-init runs it. */
function load(path) {
    window.location.pathname = path;
    const tour = fuzzyTour();
    tour.restore();
    return tour;
}

test('the start card is a modal whose Go opens stop 1, the page it names', () => {
    const tour = fuzzyTour();
    tour.start();
    assert.equal(tour.docked, false);

    tour.go();

    assert.deepEqual(opened, ['/search/users']);
    assert.equal(load('/search/users').step, 0);
});

test('on each stop the card is docked, names that page, and Next opens the following stop', () => {
    const tour = fuzzyTour();
    tour.start();
    const hrefs = tour.steps.map((s) => s.href);
    tour.go();

    for (let i = 0; i < hrefs.length; i++) {
        const here = load(opened.at(-1));
        assert.equal(here.active, true);
        assert.equal(here.docked, true);
        assert.equal(here.step, i);
        assert.equal(here.current.href, hrefs[i]);
        assert.equal(here.here, true);
        here.next();
    }

    assert.deepEqual(opened, hrefs, 'every stop opened once, in order');
    assert.equal(store.size, 0, 'Finish on the last stop ends the tour');
    assert.equal(load('/search/capability-matrix').active, false);
});

test('landing on another stop (back button, nav) shows that stop’s card', () => {
    const tour = fuzzyTour();
    tour.start();
    tour.next(); // to stop 2, products

    const back = load('/search/users');
    assert.equal(back.step, 0);

    const elsewhere = load('/search/analytics'); // not a stop: keep the saved one, offer Go
    assert.equal(elsewhere.step, 0);
    assert.equal(elsewhere.here, false);
    elsewhere.go();
    assert.equal(opened.at(-1), '/search/users');
});

test('End removes the tour, so the next page shows no card', () => {
    const tour = load('/');
    assert.equal(tour.active, false);

    tour.start();
    tour.go();
    load('/search/users').finish();

    assert.equal(load('/search/products').active, false);
});
