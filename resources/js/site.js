/*
 * KOBA Patisserie & Bakery — public website interactions.
 * Dependency-free. Page-specific modules load on demand.
 */
import { $ } from './site/util';
import { initLoader, initCurtain, initHeader, initOverlayMenu, initCursor } from './site/shell';
import { initReveals, initParallax, initHeroParallax, initScrub, initManifesto } from './site/motion';
import { initCart } from './site/cart';
import { initTabs, initPrinciples, initLocations, initFeatures, initCarousels, initHeroSliders, initDishPreviews } from './site/components';

initCurtain();
initHeader();
initOverlayMenu();
initCursor();
initCart();
initTabs();
initPrinciples();
initLocations();
initFeatures();
initHeroSliders();
initDishPreviews();
initCarousels();
initParallax();
initHeroParallax();
initScrub();
initManifesto();

initLoader(() => initReveals());

if ($('[data-order-form]')) import('./site/order').then((m) => m.initOrderForm());
if ($('[data-enquiry-form]')) import('./site/enquiry').then((m) => m.initEnquiryForms());
if ($('[data-menu-search]')) import('./site/menu').then((m) => m.initMenu());
if ($('[data-reviews]')) import('./site/reviews').then((m) => m.initReviews());
if ($('[data-xp]')) import('./site/experience').then((m) => m.initExperience());
if ($('[data-atlas]')) import('./site/atlas').then((m) => m.initAtlas());
if ($('[data-cake-dialog]')) import('./site/cakes').then((m) => m.initCakes());
if ($('[data-home]')) import('./site/home').then((m) => m.initHome());
if ($('[data-pillars]')) import('./site/home').then((m) => m.initPillarsSection());
