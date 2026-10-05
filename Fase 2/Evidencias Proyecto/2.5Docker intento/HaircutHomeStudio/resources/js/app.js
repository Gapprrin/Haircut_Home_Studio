import './bootstrap';

function initReveal() {
	if (!document.body.classList.contains('home-page')) return;

	const elements = document.querySelectorAll('.home-page-wrap > *, .home-mosaic-item, .home-products-section .product-card, .home-reviews-badge, footer.site .footer-top, footer.site .footer-bottom');
	if (!('IntersectionObserver' in window)) {
		elements.forEach((element) => element.classList.add('is-in'));
		return;
	}

	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-in');
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.01, rootMargin: '0px 0px -4% 0px' });

	elements.forEach((element, index) => {
		element.style.transitionDelay = `${Math.min(index % 8, 7) * 55}ms`;
		observer.observe(element);
	});
}

function initNavigation() {
	const toggle = document.querySelector('.nav-toggle');
	const navigation = document.getElementById('nav-acc');
	const overlay = document.querySelector('.side-nav-overlay');
	const close = document.querySelector('.side-nav-close');
	if (!toggle || !navigation) return;

	const setOpen = (open) => {
		navigation.classList.toggle('is-open', open);
		document.body.classList.toggle('nav-open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (overlay) overlay.hidden = !open;
	};

	window.setSideNavOpen = setOpen;
	toggle.addEventListener('click', () => setOpen(!navigation.classList.contains('is-open')));
	close?.addEventListener('click', () => setOpen(false));
	overlay?.addEventListener('click', () => setOpen(false));
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') setOpen(false);
	});
	window.addEventListener('pagehide', () => setOpen(false));
}

function initLoader() {
	const loader = document.getElementById('app-loader');
	const show = () => {
		window.setSideNavOpen?.(false);
		document.documentElement.classList.add('hhs-loading');
		document.body.classList.add('is-loading');
		if (loader) loader.hidden = false;
	};
	const hide = () => {
		window.setSideNavOpen?.(false);
		document.documentElement.classList.remove('hhs-loading');
		document.body.classList.remove('is-loading');
		if (loader) loader.hidden = true;
	};

	window.showAppLoader = show;
	window.hideAppLoader = hide;
	hide();
	window.addEventListener('pageshow', hide);

	const back = document.getElementById('header-back');
	back?.addEventListener('click', (event) => {
		event.preventDefault();
		if (document.referrer) {
			try {
				const referrer = new URL(document.referrer);
				if (referrer.origin === window.location.origin && referrer.pathname !== window.location.pathname) {
					window.history.back();
					return;
				}
			} catch {}
		}
		window.location.href = back.href;
	});

	document.addEventListener('submit', (event) => {
		if (!event.defaultPrevented && !event.target.hasAttribute('data-no-loader')) show();
	});
	document.addEventListener('click', (event) => {
		if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
		if (event.target.closest('.nav-toggle, .side-nav-close, .carousel-dot, .carousel-btn, [data-panel-toggle], [data-panel-close], [data-no-loader], #header-back')) return;
		const link = event.target.closest('a[href]');
		if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
		const href = link.getAttribute('href') || '';
		if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
		try {
			if (new URL(link.href, window.location.href).origin === window.location.origin) show();
		} catch {}
	});
}

function initCarousels() {
	document.querySelectorAll('.carousel-auto').forEach((root) => {
		const track = root.querySelector('.carousel-track');
		const viewport = root.querySelector('.carousel-viewport');
		const dots = root.querySelector('.carousel-dots');
		const previous = root.querySelector('.carousel-btn-prev');
		const next = root.querySelector('.carousel-btn-next');
		const slides = track ? Array.from(track.querySelectorAll('.carousel-slide')) : [];
		if (!track || !viewport || slides.length === 0) return;

		let page = 0;
		let direction = 1;
		let timer;
		const delay = Number.parseInt(root.dataset.autoplay || '5000', 10);
		const desktopCount = Number.parseInt(root.dataset.perView || '3', 10);
		const perView = () => window.matchMedia('(min-width: 900px)').matches ? Math.min(desktopCount, slides.length) : 1;
		const pageCount = () => Math.max(1, Math.ceil(slides.length / perView()));
		const maxStart = () => Math.max(0, slides.length - perView());
		const startIndex = () => Math.min(page * perView(), maxStart());

		const render = () => {
			page = Math.max(0, Math.min(page, pageCount() - 1));
			const gap = Number.parseFloat(getComputedStyle(track).gap) || 16;
			const offset = startIndex() * (slides[0].getBoundingClientRect().width + gap);
			track.style.transform = `translateX(${-offset}px)`;
			dots?.querySelectorAll('.carousel-dot').forEach((dot, index) => dot.classList.toggle('is-active', index === page));
			if (previous) previous.disabled = startIndex() === 0;
			if (next) next.disabled = startIndex() >= maxStart();
		};
		const restart = () => {
			window.clearInterval(timer);
			if (pageCount() > 1) {
				timer = window.setInterval(() => {
					if (page >= pageCount() - 1) direction = -1;
					if (page <= 0) direction = 1;
					page += direction;
					render();
				}, delay);
			}
		};
		const buildDots = () => {
			if (!dots) return;
			dots.replaceChildren();
			for (let index = 0; index < pageCount(); index += 1) {
				const dot = document.createElement('button');
				dot.type = 'button';
				dot.className = `carousel-dot${index === page ? ' is-active' : ''}`;
				dot.setAttribute('aria-label', `Ir a la diapositiva ${index + 1}`);
				dot.addEventListener('click', () => { page = index; render(); restart(); });
				dots.appendChild(dot);
			}
		};

		previous?.addEventListener('click', () => { direction = -1; page -= 1; render(); restart(); });
		next?.addEventListener('click', () => { direction = 1; page += 1; render(); restart(); });
		root.addEventListener('mouseenter', () => window.clearInterval(timer));
		root.addEventListener('mouseleave', restart);
		let startX = 0;
		let startY = 0;
		viewport.addEventListener('touchstart', (event) => {
			startX = event.changedTouches[0].clientX;
			startY = event.changedTouches[0].clientY;
		}, { passive: true });
		viewport.addEventListener('touchend', (event) => {
			const deltaX = event.changedTouches[0].clientX - startX;
			const deltaY = event.changedTouches[0].clientY - startY;
			if (Math.abs(deltaX) < 36 || Math.abs(deltaX) < Math.abs(deltaY)) return;
			direction = deltaX < 0 ? 1 : -1;
			page += direction;
			render();
			restart();
		}, { passive: true });

		buildDots();
		render();
		restart();
		window.addEventListener('resize', () => { buildDots(); render(); restart(); });
	});
}

function initPanelsAndFilters() {
	document.querySelectorAll('[data-panel-toggle]').forEach((button) => {
		button.addEventListener('click', () => {
			const panel = document.getElementById(button.dataset.panelToggle);
			if (!panel) return;
			panel.hidden = !panel.hidden;
			button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
		});
	});
	document.querySelectorAll('[data-panel-close]').forEach((button) => {
		button.addEventListener('click', () => {
			const panel = document.getElementById(button.dataset.panelClose);
			if (!panel) return;
			panel.hidden = true;
			document.querySelector(`[data-panel-toggle="${panel.id}"]`)?.setAttribute('aria-expanded', 'false');
		});
	});
	document.querySelectorAll('[data-filter-input]').forEach((input) => {
		input.addEventListener('input', () => {
			const key = input.dataset.filterInput;
			const query = input.value.trim().toLocaleLowerCase('es');
			document.querySelectorAll(`[data-filter-row="${key}"]`).forEach((row) => {
				row.hidden = query !== '' && !(row.dataset.filterText || '').includes(query);
			});
		});
	});
}

function initPhotoPicker() {
	const wrapper = document.getElementById('book-photo');
	const input = document.getElementById('book-foto');
	const drop = document.getElementById('book-photo-drop');
	const preview = document.getElementById('book-photo-preview');
	const image = preview?.querySelector('img');
	const clear = document.getElementById('book-photo-clear');
	const message = document.getElementById('book-photo-msg');
	if (!wrapper || !input || !drop || !preview || !image) return;

	const allowed = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
	let objectUrl;
	const showMessage = (text) => {
		if (!message) return;
		message.hidden = !text;
		message.textContent = text;
	};
	const reset = () => {
		if (objectUrl) URL.revokeObjectURL(objectUrl);
		objectUrl = undefined;
		preview.hidden = true;
		drop.hidden = false;
		image.removeAttribute('src');
		wrapper.classList.remove('has-file');
	};
	const setFile = (file) => {
		if (!file) return;
		if (!allowed.has(file.type)) {
			input.value = '';
			reset();
			showMessage('Solo se permiten imágenes (JPG, PNG o WEBP). No PDF.');
			return;
		}
		if (file.size > 3 * 1024 * 1024) {
			input.value = '';
			reset();
			showMessage('La imagen no puede superar 3 MB.');
			return;
		}
		showMessage('');
		if (objectUrl) URL.revokeObjectURL(objectUrl);
		objectUrl = URL.createObjectURL(file);
		image.src = objectUrl;
		preview.hidden = false;
		drop.hidden = true;
		wrapper.classList.add('has-file');
	};

	input.addEventListener('change', () => setFile(input.files?.[0]));
	clear?.addEventListener('click', () => { input.value = ''; reset(); showMessage(''); });
	['dragenter', 'dragover'].forEach((name) => drop.addEventListener(name, (event) => { event.preventDefault(); drop.classList.add('is-drag'); }));
	['dragleave', 'drop'].forEach((name) => drop.addEventListener(name, (event) => { event.preventDefault(); drop.classList.remove('is-drag'); }));
	drop.addEventListener('drop', (event) => {
		const file = event.dataTransfer?.files?.[0];
		if (!file) return;
		try {
			const transfer = new DataTransfer();
			transfer.items.add(file);
			input.files = transfer.files;
		} catch {}
		setFile(file);
	});
}

function initAvailability() {
	const month = document.querySelector('[data-availability-month]');
	const year = document.querySelector('[data-availability-year]');
	const base = document.querySelector('[data-availability-url]')?.dataset.availabilityUrl;
	const navigate = () => {
		if (month && year && base) window.location.href = `${base}?anio=${encodeURIComponent(year.value)}&mes=${encodeURIComponent(month.value)}`;
	};
	month?.addEventListener('change', navigate);
	year?.addEventListener('change', navigate);

	const visibleYear = document.querySelector('[data-visible-year]');
	const showYear = () => {
		document.querySelectorAll('[data-visible-panel]').forEach((panel) => {
			panel.hidden = panel.dataset.visiblePanel !== visibleYear?.value;
		});
	};
	visibleYear?.addEventListener('change', showYear);
	showYear();
}

function initHours() {
	const labels = { pendiente: 'Pendiente', confirmada: 'Confirmada', realizada: 'Realizada', realizando: 'Realizando', finalizado: 'Finalizado' };
	const parse = (value) => {
		const parts = (value || '').split(/[-T:]/).map(Number);
		return parts.length >= 5 ? new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5] || 0) : null;
	};
	const tick = () => {
		const current = Date.now();
		document.querySelectorAll('.hours-item').forEach((item) => {
			const start = parse(item.dataset.start);
			const end = parse(item.dataset.end);
			if (!start || !end) return;
			if (current >= end.getTime() + 5 * 60 * 1000) {
				item.remove();
				return;
			}
			let state = item.dataset.base || 'pendiente';
			if (current >= end.getTime()) state = 'finalizado';
			else if (current >= start.getTime()) state = 'realizando';
			const badge = item.querySelector('.hours-status');
			if (badge) {
				badge.className = `dash-pill hours-status st-${state}`;
				badge.textContent = labels[state] || state;
			}
		});
		document.querySelectorAll('[data-hours-day]').forEach((day) => {
			const count = day.querySelectorAll('.hours-item').length;
			const label = day.querySelector('.hours-count');
			if (label) label.textContent = `${count} ${count === 1 ? 'persona' : 'personas'}`;
			if (count === 0) day.hidden = true;
		});
	};
	tick();
	if (document.querySelector('.hours-item')) window.setInterval(tick, 15000);
}

function initSmallInteractions() {
	const map = document.querySelector('.footer-map');
	const loadMap = () => {
		if (map?.dataset.src && map.src !== map.dataset.src) map.src = map.dataset.src;
	};
	if ('requestIdleCallback' in window) window.requestIdleCallback(loadMap, { timeout: 2500 });
	else window.setTimeout(loadMap, 1200);

	document.querySelectorAll('.alert[data-autohide]').forEach((alert) => {
		window.setTimeout(() => {
			alert.classList.add('is-gone');
			window.setTimeout(() => alert.remove(), 260);
		}, Number.parseInt(alert.dataset.autohide || '2500', 10));
	});

	if (document.body.classList.contains('book-page')) {
		const key = 'hhs-book-scroll';
		const saved = sessionStorage.getItem(key);
		if (saved) {
			window.scrollTo(0, Number.parseInt(saved, 10) || 0);
			sessionStorage.removeItem(key);
		}
		document.addEventListener('click', (event) => {
			if (event.target.closest('a[href*="/reservar"]')) sessionStorage.setItem(key, String(window.scrollY));
		});
	}
}

function initAiStudio() {
	const input = document.getElementById('ai-photo');
	const drop = document.querySelector('[data-ai-photo-drop]');
	const preview = document.querySelector('[data-ai-photo-preview]');
	const image = preview?.querySelector('img');
	const change = document.querySelector('[data-ai-photo-change]');
	const error = document.querySelector('[data-ai-photo-error]');
	let objectUrl;

	const reset = () => {
		if (objectUrl) URL.revokeObjectURL(objectUrl);
		objectUrl = undefined;
		if (input) input.value = '';
		if (preview) preview.hidden = true;
		if (drop) drop.hidden = false;
		if (change) change.hidden = true;
		image?.removeAttribute('src');
		if (error) error.hidden = true;
	};
	const select = (file) => {
		if (!file || !input || !preview || !image || !drop) return;
		if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 6 * 1024 * 1024) {
			reset();
			if (error) {
				error.textContent = file.size > 6 * 1024 * 1024 ? 'La fotografía no puede superar 6 MB.' : 'Usa una fotografía JPG o PNG.';
				error.hidden = false;
			}
			return;
		}
		if (objectUrl) URL.revokeObjectURL(objectUrl);
		objectUrl = URL.createObjectURL(file);
		image.src = objectUrl;
		preview.hidden = false;
		drop.hidden = true;
		if (change) change.hidden = false;
		if (error) error.hidden = true;
	};

	input?.addEventListener('change', () => select(input.files?.[0]));
	['dragenter', 'dragover'].forEach((name) => drop?.addEventListener(name, (event) => {
		event.preventDefault();
		drop.classList.add('is-drag');
	}));
	['dragleave', 'drop'].forEach((name) => drop?.addEventListener(name, (event) => {
		event.preventDefault();
		drop.classList.remove('is-drag');
	}));
	drop?.addEventListener('drop', (event) => {
		const file = event.dataTransfer?.files?.[0];
		if (!file || !input) return;
		try {
			const transfer = new DataTransfer();
			transfer.items.add(file);
			input.files = transfer.files;
		} catch {}
		select(file);
	});

	const categoryTabs = Array.from(document.querySelectorAll('[data-ai-category-tab]'));
	const categoryPanels = Array.from(document.querySelectorAll('[data-ai-category-panel]'));
	categoryTabs.forEach((tab) => {
		tab.addEventListener('click', () => {
			if (tab.getAttribute('aria-selected') === 'true') return;
			const category = tab.dataset.aiCategoryTab;
			document.querySelectorAll('input[name="servicio_id"]').forEach((radio) => {
				radio.checked = false;
			});
			categoryTabs.forEach((item) => {
				const active = item === tab;
				item.classList.toggle('is-active', active);
				item.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			categoryPanels.forEach((panel) => {
				const active = panel.dataset.aiCategoryPanel === category;
				panel.hidden = !active;
				panel.querySelectorAll('input[name="servicio_id"]').forEach((radio) => {
					radio.required = active;
				});
			});
		});
	});

	const statusRoot = document.querySelector('[data-ai-status-url]');
	if (statusRoot) {
		const poll = window.setInterval(async () => {
			try {
				const response = await fetch(statusRoot.dataset.aiStatusUrl, { headers: { Accept: 'application/json' } });
				if (!response.ok) return;
				const status = await response.json();
				if (!['pending', 'processing'].includes(status.status)) {
					window.clearInterval(poll);
					window.location.replace(status.show_url);
				}
			} catch {}
		}, 3000);
	}
}

function initBookingWizard() {
	const wizard = document.querySelector('[data-book-wizard]');
	if (!wizard) return;

	const stepOrder = ['servicio', 'fecha', 'referencia', 'lugar', 'resumen'];
	const steps = new Map([...wizard.querySelectorAll('[data-book-step]')].map((step) => [step.dataset.bookStep, step]));
	const progress = new Map([...wizard.querySelectorAll('[data-book-progress]')].map((item) => [item.dataset.bookProgress, item]));
	const form = wizard.querySelector('.book-wizard-form');
	const categoryTabs = [...wizard.querySelectorAll('[data-book-category-tab]')];
	const categoryPanels = [...wizard.querySelectorAll('[data-book-category-panel]')];
	const serviceInput = wizard.querySelector('input[name="servicio_id"]');
	const serviceOptions = [...wizard.querySelectorAll('[data-book-service-option]')];
	let current = stepOrder.includes(wizard.dataset.bookInitialStep) ? wizard.dataset.bookInitialStep : 'servicio';
	let selectedServiceUrl;

	const selectService = (option) => {
		if (!option || !serviceInput) return;
		serviceInput.value = option.dataset.bookServiceId;
		selectedServiceUrl = option.dataset.bookServiceUrl;
		serviceOptions.forEach((item) => {
			const selected = item === option;
			item.classList.toggle('is-selected', selected);
			const label = item.querySelector(':scope > span');
			if (label) label.textContent = selected ? 'Seleccionado' : 'Elegir';
		});
	};

	const updatePlace = () => {
		const selected = wizard.querySelector('input[name="lugar"]:checked');
		const label = selected?.closest('.book-place-option')?.querySelector('strong')?.textContent?.trim();
		const summary = wizard.querySelector('[data-book-summary-place]');
		if (label && summary) summary.textContent = label;
	};
	const show = (target, scroll = true) => {
		if (!steps.has(target)) return;
		const previous = steps.get(current);
		const previousHeight = form?.getBoundingClientRect().height || 0;
		current = target;
		const currentIndex = stepOrder.indexOf(target);
		steps.forEach((step, key) => {
			const active = key === target;
			step.hidden = !active;
			step.classList.remove('is-entering');
			step.classList.toggle('is-active', active);
		});
		const activeStep = steps.get(target);
		if (form && previous && previous !== activeStep && previousHeight) {
			form.style.height = `${previousHeight}px`;
			activeStep.classList.add('is-entering');
			requestAnimationFrame(() => {
				form.style.height = `${activeStep.getBoundingClientRect().height}px`;
				requestAnimationFrame(() => activeStep.classList.remove('is-entering'));
			});
			window.setTimeout(() => { form.style.height = ''; }, 460);
		}
		progress.forEach((item, key) => {
			const index = stepOrder.indexOf(key);
			item.classList.toggle('is-active', key === target);
			item.classList.toggle('is-complete', index < currentIndex);
			item.setAttribute('aria-current', key === target ? 'step' : 'false');
		});
		updatePlace();
		if (scroll) wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
	};
	const canAdvance = (target) => {
		if (target !== 'referencia') return true;
		const hasDate = Boolean(wizard.querySelector('input[name="fecha"]')?.value);
		const hasTime = Boolean(wizard.querySelector('input[name="hora"]')?.value);
		const warning = wizard.querySelector('[data-book-step-warning]');
		if (warning) warning.hidden = hasDate && hasTime;
		return hasDate && hasTime;
	};

	wizard.querySelectorAll('[data-book-next]').forEach((button) => {
		button.addEventListener('click', () => {
			const target = button.dataset.bookNext;
			if (current === 'servicio' && selectedServiceUrl) {
				window.location.assign(selectedServiceUrl);
				return;
			}
			if (canAdvance(target)) show(target);
		});
	});
	wizard.querySelectorAll('[data-book-back]').forEach((button) => {
		button.addEventListener('click', () => show(button.dataset.bookBack));
	});
	categoryTabs.forEach((tab) => {
		tab.addEventListener('click', (event) => {
			if (tab.getAttribute('aria-selected') === 'true') return;
			event.preventDefault();
			const category = tab.dataset.bookCategoryTab;
			categoryTabs.forEach((item) => {
				const active = item === tab;
				item.classList.toggle('is-active', active);
				item.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			categoryPanels.forEach((panel) => {
				const active = panel.dataset.bookCategoryPanel === category;
				panel.hidden = !active;
				if (active) {
					panel.classList.remove('is-entering');
					requestAnimationFrame(() => panel.classList.add('is-entering'));
				}
			});
			const firstService = wizard.querySelector(`[data-book-category-panel="${category}"] [data-book-service-option]`);
			selectService(firstService);
			selectedServiceUrl = tab.dataset.bookCategoryServiceUrl;
		});
	});
	serviceOptions.forEach((option) => {
		option.addEventListener('click', (event) => {
			event.preventDefault();
			selectService(option);
		});
	});
	wizard.querySelectorAll('input[name="lugar"]').forEach((input) => input.addEventListener('change', updatePlace));

	show(current, false);
}

initReveal();
initNavigation();
initLoader();
initCarousels();
initPanelsAndFilters();
initPhotoPicker();
initAvailability();
initHours();
initSmallInteractions();
initAiStudio();
initBookingWizard();
