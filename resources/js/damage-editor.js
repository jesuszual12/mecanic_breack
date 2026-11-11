import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

const editor = document.querySelector('[data-damage-editor]');

if (editor) {
	const form = editor.closest('form');
	const view = editor.querySelector('[data-damage-view]');
	const partsContainer = editor.querySelector('[data-damage-parts]');
	const selectedContainer = editor.querySelector('[data-damage-selected]');
	const hiddenInput = editor.querySelector('input[name="damage_points_json"]');
	const partLabels = {
		hood: 'Cofre',
		roof: 'Techo',
		trunk: 'Cajuela',
		front_bumper: 'Defensa delantera',
		rear_bumper: 'Defensa trasera',
		left_front_door: 'Puerta delantera izquierda',
		right_front_door: 'Puerta delantera derecha',
		left_rear_door: 'Puerta trasera izquierda',
		right_rear_door: 'Puerta trasera derecha',
		left_front_fender: 'Salpicadera delantera izquierda',
		right_front_fender: 'Salpicadera delantera derecha',
		left_rear_fender: 'Salpicadera trasera izquierda',
		right_rear_fender: 'Salpicadera trasera derecha',
	};
	const selected = new Map();
	let scene;
	let camera;
	let renderer;
	let controls;
	let raycaster;
	let selectableParts = [];
	let pointerStart;

	try {
		const initialParts = JSON.parse(hiddenInput.value || '[]');
		if (!Array.isArray(initialParts)) {
			throw new TypeError('Los daños guardados deben ser una lista.');
		}
		initialParts.forEach((item) => {
			if (partLabels[item.part] && typeof item.detail === 'string') {
				selected.set(item.part, item.detail);
			}
		});
	} catch (error) {
		selectedContainer.textContent = 'No se pudieron leer los daños previos. Vuelve a seleccionar las piezas.';
		console.error('Invalid saved vehicle damage data.', error);
	}

	const syncHiddenInput = () => {
		hiddenInput.value = JSON.stringify(
			Array.from(selected, ([part, detail]) => ({ part, detail })),
		);
	};

	const togglePart = (part) => {
		if (!partLabels[part]) return;

		if (selected.has(part)) {
			selected.delete(part);
		} else {
			selected.set(part, '');
		}

		renderSelection();
		updatePartColors();
	};

	const renderSelection = () => {
		partsContainer.replaceChildren();
		Object.entries(partLabels).forEach(([part, label]) => {
			const button = document.createElement('button');
			button.type = 'button';
			button.className = `btn btn-sm ${selected.has(part) ? 'btn-danger' : 'btn-outline-secondary'}`;
			button.textContent = label;
			button.setAttribute('aria-pressed', String(selected.has(part)));
			button.addEventListener('click', () => togglePart(part));
			partsContainer.append(button);
		});

		selectedContainer.replaceChildren();
		if (!selected.size) {
			selectedContainer.textContent = 'Ninguna pieza marcada.';
			syncHiddenInput();
			return;
		}

		selected.forEach((detail, part) => {
			const card = document.createElement('label');
			card.className = 'damage-note';
			const title = document.createElement('span');
			title.textContent = partLabels[part];
			const input = document.createElement('textarea');
			input.rows = 2;
			input.maxLength = 300;
			input.required = true;
			input.placeholder = 'Describe el daño visible';
			input.value = detail;
			input.setAttribute('aria-label', `Detalle del daño: ${partLabels[part]}`);
			input.addEventListener('input', () => {
				selected.set(part, input.value);
				syncHiddenInput();
			});
			const remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'btn btn-sm btn-link text-danger';
			remove.textContent = 'Quitar';
			remove.addEventListener('click', () => togglePart(part));
			card.append(title, input, remove);
			selectedContainer.append(card);
		});
		syncHiddenInput();
	};

	const updatePartColors = () => {
		selectableParts.forEach((mesh) => {
			mesh.material.color.set(selected.has(mesh.userData.part) ? '#df694f' : '#54836a');
			mesh.material.emissive.set(selected.has(mesh.userData.part) ? '#5d160b' : '#000000');
		});
	};

	const addPart = (part, size, position, color = '#54836a') => {
		const mesh = new THREE.Mesh(
			new THREE.BoxGeometry(...size),
			new THREE.MeshStandardMaterial({ color, roughness: 0.62, metalness: 0.12 }),
		);
		mesh.position.set(...position);
		mesh.userData.part = part;
		mesh.castShadow = true;
		mesh.receiveShadow = true;
		scene.add(mesh);
		selectableParts.push(mesh);
	};

	const addWindow = (size, position, rotationY = 0) => {
		const windowMesh = new THREE.Mesh(
			new THREE.BoxGeometry(...size),
			new THREE.MeshStandardMaterial({ color: '#91b7ba', roughness: 0.22, metalness: 0.24 }),
		);
		windowMesh.position.set(...position);
		windowMesh.rotation.y = rotationY;
		scene.add(windowMesh);
	};

	const addWheel = (x, z) => {
		const wheel = new THREE.Mesh(
			new THREE.CylinderGeometry(0.39, 0.39, 0.25, 28),
			new THREE.MeshStandardMaterial({ color: '#252d2b', roughness: 0.91 }),
		);
		wheel.rotation.x = Math.PI / 2;
		wheel.position.set(x, 0.39, z);
		wheel.castShadow = true;
		scene.add(wheel);
	};

	const initializeVehicle = () => {
		try {
			scene = new THREE.Scene();
			scene.background = new THREE.Color('#edf1ec');
			scene.fog = new THREE.Fog('#edf1ec', 12, 24);
			camera = new THREE.PerspectiveCamera(42, view.clientWidth / view.clientHeight, 0.1, 100);
			camera.position.set(-5.4, 4.4, 6.6);
			renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
			renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
			renderer.setSize(view.clientWidth, view.clientHeight);
			renderer.shadowMap.enabled = true;
			renderer.shadowMap.type = THREE.PCFShadowMap;
			view.append(renderer.domElement);
			controls = new OrbitControls(camera, renderer.domElement);
			controls.enableDamping = true;
			controls.target.set(0, 0.85, 0);
			controls.minDistance = 5;
			controls.maxDistance = 12;
			raycaster = new THREE.Raycaster();
			renderer.domElement.addEventListener('pointerdown', (event) => {
				pointerStart = { x: event.clientX, y: event.clientY };
			});

			scene.add(new THREE.HemisphereLight('#ffffff', '#829184', 2.2));
			const sunlight = new THREE.DirectionalLight('#ffffff', 3.2);
			sunlight.position.set(-4, 8, 5);
			sunlight.castShadow = true;
			scene.add(sunlight);

			const floor = new THREE.Mesh(
				new THREE.PlaneGeometry(200, 200),
				new THREE.MeshStandardMaterial({ color: '#edf1ec', roughness: 1 }),
			);
			floor.rotation.x = -Math.PI / 2;
			floor.position.y = -0.02;
			floor.receiveShadow = true;
			scene.add(floor);

			addPart('front_bumper', [0.26, 0.24, 1.72], [-2.03, 0.62, 0], '#3d674f');
			addPart('hood', [1.32, 0.2, 1.7], [-1.22, 0.97, 0]);
			addPart('left_front_fender', [0.72, 0.2, 0.22], [-1.3, 0.88, -0.86]);
			addPart('right_front_fender', [0.72, 0.2, 0.22], [-1.3, 0.88, 0.86]);
			addPart('left_front_door', [0.88, 0.47, 0.14], [-0.38, 0.84, -0.88]);
			addPart('right_front_door', [0.88, 0.47, 0.14], [-0.38, 0.84, 0.88]);
			addPart('left_rear_door', [0.8, 0.47, 0.14], [0.58, 0.84, -0.88]);
			addPart('right_rear_door', [0.8, 0.47, 0.14], [0.58, 0.84, 0.88]);
			addPart('left_rear_fender', [0.66, 0.2, 0.22], [1.42, 0.88, -0.86]);
			addPart('right_rear_fender', [0.66, 0.2, 0.22], [1.42, 0.88, 0.86]);
			addPart('trunk', [0.94, 0.2, 1.68], [1.49, 0.97, 0]);
			addPart('rear_bumper', [0.26, 0.24, 1.72], [2.03, 0.62, 0], '#3d674f');

			const roof = new THREE.Mesh(
				new THREE.BoxGeometry(1.48, 0.52, 1.38),
				new THREE.MeshStandardMaterial({ color: '#437357', roughness: 0.68 }),
			);
			roof.position.set(0.1, 1.38, 0);
			roof.userData.part = 'roof';
			roof.castShadow = true;
			scene.add(roof);
			selectableParts.push(roof);

			addWindow([0.11, 0.43, 1.38], [-0.76, 1.27, 0], -0.34);
			addWindow([0.11, 0.43, 1.38], [0.96, 1.27, 0], 0.34);
			[-1.28, 1.35].forEach((x) => [-0.91, 0.91].forEach((z) => addWheel(x, z)));

			renderSelection();
			updatePartColors();
			renderer.domElement.addEventListener('click', (event) => {
				if (pointerStart && Math.hypot(event.clientX - pointerStart.x, event.clientY - pointerStart.y) > 5) {
					pointerStart = undefined;
					return;
				}
				pointerStart = undefined;
				const bounds = renderer.domElement.getBoundingClientRect();
				const pointer = new THREE.Vector2(
					((event.clientX - bounds.left) / bounds.width) * 2 - 1,
					-((event.clientY - bounds.top) / bounds.height) * 2 + 1,
				);
				raycaster.setFromCamera(pointer, camera);
				const hit = raycaster.intersectObjects(selectableParts, false)[0];
				if (hit?.object.userData.part) togglePart(hit.object.userData.part);
			});

			const resize = () => {
				const width = view.clientWidth;
				const height = view.clientHeight;
				if (!width || !height) return;
				camera.aspect = width / height;
				camera.updateProjectionMatrix();
				renderer.setSize(width, height);
			};
			new ResizeObserver(resize).observe(view);
			const animate = () => {
				if (!view.isConnected) {
					renderer.dispose();
					controls.dispose();
					return;
				}
				controls.update();
				renderer.render(scene, camera);
				requestAnimationFrame(animate);
			};
			animate();
		} catch (error) {
			const message = document.createElement('p');
			message.className = 'damage-renderer-error';
			message.textContent = 'No fue posible cargar el modelo 3D en este navegador. Puedes seleccionar las piezas con los botones.';
			view.replaceChildren(message);
			console.error('Could not initialize the 3D damage inspector.', error);
		}
	};

	editor.querySelector('[data-damage-clear]').addEventListener('click', () => {
		selected.clear();
		renderSelection();
		updatePartColors();
	});

	form.addEventListener('submit', () => {
		syncHiddenInput();
	});

	renderSelection();
	initializeVehicle();
}

const inventorySearch = document.querySelector('[data-inventory-search]');

if (inventorySearch) {
	const rows = Array.from(document.querySelectorAll('[data-inventory-search-row]'));
	const noResults = document.querySelector('.inventory-no-results');
	inventorySearch.addEventListener('input', () => {
		const query = inventorySearch.value.trim().toLocaleLowerCase();
		let visibleCount = 0;
		rows.forEach((row) => {
			const matches = row.dataset.search.includes(query);
			row.hidden = !matches;
			if (matches) visibleCount += 1;
		});
		if (noResults) noResults.hidden = visibleCount > 0;
	});
}
