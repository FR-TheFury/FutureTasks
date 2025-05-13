
// Animation Three.js pour l'arrière-plan
document.addEventListener('DOMContentLoaded', function() {
  // Initialisation de la scène Three.js
  const canvas = document.getElementById('bg-animation');
  
  if (!canvas) return;
  
  // Création de la scène, caméra et renderer
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
  
  const renderer = new THREE.WebGLRenderer({ 
    alpha: true,
    antialias: true
  });
  
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(window.devicePixelRatio);
  canvas.appendChild(renderer.domElement);
  
  // Création de particules
  const particleGeometry = new THREE.BufferGeometry();
  const particleCount = 1500;
  
  const particlePositions = new Float32Array(particleCount * 3);
  const particleColors = new Float32Array(particleCount * 3);
  
  for (let i = 0; i < particleCount; i++) {
    // Position
    particlePositions[i * 3] = (Math.random() - 0.5) * 25;      // X
    particlePositions[i * 3 + 1] = (Math.random() - 0.5) * 25;  // Y
    particlePositions[i * 3 + 2] = (Math.random() - 0.5) * 25;  // Z
    
    // Couleur
    particleColors[i * 3] = Math.random() * 0.2;                // R
    particleColors[i * 3 + 1] = 0.3 + Math.random() * 0.7;      // G
    particleColors[i * 3 + 2] = 0.8 + Math.random() * 0.2;      // B
  }
  
  particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
  particleGeometry.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));
  
  const particleMaterial = new THREE.PointsMaterial({
    size: 0.05,
    transparent: true,
    opacity: 0.7,
    vertexColors: true,
    blending: THREE.AdditiveBlending
  });
  
  const particleSystem = new THREE.Points(particleGeometry, particleMaterial);
  scene.add(particleSystem);
  
  // Position de la caméra
  camera.position.z = 5;
  
  // Variables pour le mouvement de la souris
  let mouseX = 0;
  let mouseY = 0;
  let targetMouseX = 0;
  let targetMouseY = 0;
  
  // Gestion des événements de la souris
  document.addEventListener('mousemove', (event) => {
    targetMouseX = (event.clientX / window.innerWidth - 0.5) * 0.1;
    targetMouseY = (event.clientY / window.innerHeight - 0.5) * 0.1;
  });
  
  // Fonction d'animation
  function animate() {
    requestAnimationFrame(animate);
    
    // Animation lisse du mouvement de la souris
    mouseX += (targetMouseX - mouseX) * 0.05;
    mouseY += (targetMouseY - mouseY) * 0.05;
    
    // Rotation du système de particules
    particleSystem.rotation.x += 0.0005;
    particleSystem.rotation.y += 0.001;
    
    // Mouvement basé sur la position de la souris
    particleSystem.rotation.y += mouseX * 0.05;
    particleSystem.rotation.x += mouseY * 0.05;
    
    // Rendu de la scène
    renderer.render(scene, camera);
  }
  
  // Gestion du redimensionnement de la fenêtre
  function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  }
  
  window.addEventListener('resize', onWindowResize);
  
  // Démarrer l'animation
  animate();
});
