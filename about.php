<?php
session_start();
include('./includes/header.php');
include('./includes/config.php');
?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>

:root {
  --cream-harvest: #FAF3E3;
  --golden-sand: #CFBB9F;
  --chestnut-grove: #5A4939;
  --smoky-oak: #6D6053;
  --cocoa-bark: #2C1A11;

  --bg-color: var(--cream-harvest);
  --accent: var(--golden-sand);
  --text-dark: var(--cocoa-bark);
  --text-light: var(--cream-harvest);
  --btn-bg: var(--chestnut-grove);
  --btn-hover: var(--smoky-oak);
  --card-bg: var(--cream-harvest);
  --shadow-color: rgba(44, 26, 17, 0.15);
  --border-color: rgba(95, 73, 57, 0.2);
}

/* base */
body {
  font-family: "Open Sans", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  color: var(--text-dark);
  background-color: var(--bg-color);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* About Hero */
.about-hero {
  color: var(--text-dark);
  padding: 130px 0;
  text-align: center;
  position: relative;
  background: linear-gradient(rgba(44,26,17,0.55), rgba(93,80,67,0.45)), url('./assets/hero-bg.jpg') center/cover no-repeat;
}

.about-hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(44,26,17,0.25);
}

.about-hero .container {
  position: relative;
  z-index: 2;
}

.about-hero h1 {
  font-family: 'Playfair Display', serif;
  font-size: 3rem;
  font-weight: 700;
  color: var(--text-light);
}

.about-hero p {
  font-size: 1.15rem;
  margin-top: 15px;
  line-height: 1.8;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
  color: var(--cream-harvest);
}

/* About Section */
.about-section {
  padding: 90px 0;
  text-align: center;
}

.about-section h2 {
  font-family: 'Playfair Display', serif;
  font-weight: 600;
  margin-bottom: 25px;
  color: var(--cocoa-bark);
}

.about-section p {
  font-size: 1.05rem;
  line-height: 1.9;
  color: var(--smoky-oak);
}

/* Values Section */
.values {
  padding: 80px 0;
  background: var(--cream-harvest);
}

.values h2 {
  font-family: 'Playfair Display', serif;
  color: var(--cocoa-bark);
  font-weight: 600;
}

.values .card {
  border: none;
  background: var(--chestnut-grove);
  border-radius: 12px;
  transition: all 0.3s ease;
  height: 100%;
  color: var(--text-light);
  box-shadow: 0 6px 18px var(--shadow-color);
}

.values .card:hover {
  transform: translateY(-6px);
  box-shadow: 0 10px 26px rgba(44,26,17,0.25);
}

.values .card i {
  color:  rgba(255, 204, 63, 0.9)
}

.values .card h5 {
  font-family: 'Playfair Display', serif;
  font-weight: 600;
  margin-top: 15px;
  color: var(--text-dark);
}

.values .card p {
  font-size: 0.95rem;
  color: var(--text-dark);
}

/* CTA Section */
.cta {
  background: transparent;
  padding: 90px 0;
  border-top: 1px solid rgba(44,26,17,0.1);
  text-align: center;
}

.cta h2 {
  font-family: 'Playfair Display', serif;
  font-weight: 600;
  color: var(--cocoa-bark);
}

.cta p {
  color: var(--smoky-oak);
}

.btn-dark {
  border-radius: 6px;
  font-weight: 500;
  transition: 0.3s;
  background: var(--btn-bg);
  color: var(--text-light);
  border: none;
  padding: 10px 20px;
}

.btn-dark:hover {
  background-color: var(--btn-hover);
  transform: translateY(-2px);
  color: var(--text-light);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .about-hero h1 { font-size: 2.2rem; }
}
@media (max-width: 480px) {
  .about-hero h1 { font-size: 1.8rem; }
}
</style>

<!-- Hero -->
<section class="about-hero">
  <div class="container">
    <h1>About <span style="font-style: italic;">L'Essence</span> Perfume Retailing</h1>
    <p>Bringing luxury scents closer to every Filipino — because smelling good shouldn’t have to cost a fortune.</p>
  </div>
</section>

<!-- Our Story -->
<section class="about-section text-center">
  <div class="container">
    <h2>Our Story</h2>
    <p class="mx-auto" style="max-width: 800px;">
      <strong>L'Essence Perfume Retailing</strong> started in 2022 with one simple idea — to make quality perfumes accessible to everyone.
      As perfume enthusiasts from the Philippines, we wanted to share our passion for fragrances that inspire confidence and express personality.
      <br><br>
      From popular imported scents to locally blended fragrances, we handpick each bottle to ensure authenticity, affordability, and premium quality.
      Whether you’re looking for your “pabango pang-araw-araw” or a special scent for occasions, we’ve got something that fits every story.
    </p>
  </div>
</section>

<!-- Values -->
<section class="values text-center">
  <div class="container">
    <h2>Our Core Values</h2>
    <div class="row g-4 mt-4 justify-content-center">
      <div class="col-md-3 col-sm-6">
        <div class="card p-4">
          <i class="bi bi-gem fs-1 mb-3"></i>
          <h5>Elegance</h5>
          <p>We believe that confidence begins with how you carry yourself — and your scent is part of that elegance.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card p-4">
          <i class="bi bi-heart fs-1 mb-3"></i>
          <h5>Passion</h5>
          <p>Perfume is our love language. We continuously search for scents that make people feel beautiful, bold, and unforgettable.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card p-4">
          <i class="bi bi-shield-check fs-1 mb-3"></i>
          <h5>Authenticity</h5>
          <p>We guarantee only 100% genuine and long-lasting perfumes, sourced from trusted distributors and local perfumers.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta text-center">
  <div class="container">
    <h2 class="fw-bold mb-3">Find Your Everyday Signature Scent</h2>
    <p class="text-muted mb-4">Explore our collection of imported and local fragrances — made for every mood, moment, and milestone.</p>
    <a href="index.php#popular-products" class="btn btn-dark px-4 py-2">Shop Now</a>
  </div>
</section>

<?php include('./includes/footer.php'); ?>
