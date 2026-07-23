@extends('layouts.app')

@section('title', $gallery->title)

@section('content')
<div class="container-fluid mt-4">
    <div class="mb-4">
        <a href="{{ route('public.gallery.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Gallery
        </a>
    </div>

    <div class="row gy-4 mb-5">
        <div class="col-lg-8">
            @if($gallery->cover_image)
                <img src="{{ Storage::disk('public')->url($gallery->cover_image) }}" class="img-fluid rounded-4 shadow-sm" alt="{{ $gallery->title }}" style="width:100%; object-fit:cover; max-height:420px;" loading="lazy">
            @endif
        </div>
        <div class="col-lg-4">
            <div class="p-4 rounded-4 shadow-sm h-100" style="background:#fff; border:1px solid rgba(27,48,34,0.08);">
                <h1 class="h3">{{ $gallery->title }}</h1>
                <p class="text-muted">{{ $gallery->description }}</p>
                <div class="mb-3">
                    <span class="badge bg-warning text-dark me-2">{{ $gallery->category ?? 'Uncategorized' }}</span>
                    <span class="badge bg-{{ $gallery->visibility === 'public' ? 'success' : 'dark' }}">{{ ucfirst($gallery->visibility) }}</span>
                </div>
                <div class="d-flex gap-3 text-muted small">
                    <div><i class="bi bi-images me-1"></i>{{ $gallery->images->count() }} images</div>
                    <div><i class="bi bi-eye me-1"></i>{{ $gallery->views }} views</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($gallery->images as $image)
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm overflow-hidden rounded-4 gallery-image-card" onclick="openLightbox({{ $loop->index }})">
                    <img src="{{ Storage::disk('public')->url($image->path) }}" class="card-img-top" alt="{{ $image->file_name }}" style="height:260px;object-fit:cover;cursor:pointer;" loading="lazy">
                </div>
            </div>
        @empty
            <div class="col-12 text-muted">No images in this album yet.</div>
        @endforelse
    </div>
</div>

<div class="lightbox" id="lightbox">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <button class="lightbox-play" id="lightbox-play" onclick="toggleSlideshow()">
        <i class="bi bi-play-fill"></i>
    </button>
    <button class="lightbox-nav lightbox-prev" onclick="prevImage()">
        <i class="bi bi-chevron-left"></i>
    </button>
    <button class="lightbox-nav lightbox-next" onclick="nextImage()">
        <i class="bi bi-chevron-right"></i>
    </button>
    <div class="lightbox-img-wrapper">
        <img src="" alt="" id="lightbox-img">
    </div>
    <div class="lightbox-counter" id="lightbox-counter"></div>
    <div class="lightbox-caption" id="lightbox-caption"></div>
    <div class="lightbox-thumbnails" id="lightbox-thumbnails"></div>
</div>
@endsection

@php
    $galleryImagesJson = $gallery->images->map(function ($img) {
        return [
            'path' => Storage::disk('public')->url($img->path),
            'name' => $img->file_name,
            'created_at' => $img->created_at->format('M d, Y'),
        ];
    })->toJson();
@endphp

@push('styles')
<style>
    .gallery-image-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    .gallery-image-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 40px rgba(27,67,50,0.14);
    }
    .gallery-image-card img {
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .gallery-image-card:hover img {
        transform: scale(1.06);
    }

    .lightbox {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0,0,0,0.92);
        backdrop-filter: blur(20px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    .lightbox.active {
        opacity: 1;
        visibility: visible;
    }
    .lightbox-img-wrapper {
        position: relative;
        max-width: 90vw;
        max-height: 85vh;
        transform: scale(0.9);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .lightbox.active .lightbox-img-wrapper {
        transform: scale(1);
    }
    .lightbox-img-wrapper img {
        max-width: 100%;
        max-height: 85vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .lightbox-close {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
    }
    .lightbox-close:hover {
        background: rgba(255,255,255,0.2);
        transform: rotate(90deg);
    }
    .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
    }
    .lightbox-nav:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-50%) scale(1.1);
    }
    .lightbox-prev { left: 1.5rem; }
    .lightbox-next { right: 1.5rem; }
    .lightbox-counter {
        position: absolute;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.6);
        color: #fff;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        z-index: 10;
    }
    .lightbox-caption {
        position: absolute;
        bottom: 3.5rem;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.7);
        color: #fff;
        padding: 0.6rem 1.2rem;
        border-radius: 12px;
        font-size: 0.85rem;
        text-align: center;
        max-width: 80vw;
        z-index: 10;
    }
    .lightbox-thumbnails {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        gap: 0.5rem;
        padding: 1rem;
        overflow-x: auto;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        z-index: 10;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.3) transparent;
    }
    .lightbox-thumbnails::-webkit-scrollbar {
        height: 6px;
    }
    .lightbox-thumbnails::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.3);
        border-radius: 3px;
    }
    .lightbox-thumb {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
        cursor: pointer;
        border: 2px solid transparent;
        opacity: 0.6;
        transition: all 0.3s ease;
    }
    .lightbox-thumb:hover, .lightbox-thumb.active {
        opacity: 1;
        border-color: var(--clr-honey);
    }
    .lightbox-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .lightbox-play {
        position: absolute;
        top: 1.5rem;
        left: 1.5rem;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
    }
    .lightbox-play:hover {
        background: rgba(255,255,255,0.25);
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    const images = <?php echo $galleryImagesJson; ?>;
    let currentIndex = 0;
    let slideshowInterval = null;
    let isSlideshowPlaying = false;
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCounter = document.getElementById('lightbox-counter');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxThumbnails = document.getElementById('lightbox-thumbnails');
    const lightboxPlay = document.getElementById('lightbox-play');
    let touchStartX = 0;
    let touchEndX = 0;

    function buildThumbnails() {
        lightboxThumbnails.innerHTML = '';
        images.forEach((img, index) => {
            const thumb = document.createElement('div');
            thumb.className = 'lightbox-thumb';
            thumb.onclick = () => goToImage(index);
            const thumbImg = document.createElement('img');
            thumbImg.src = img.path;
            thumbImg.loading = 'lazy';
            thumb.appendChild(thumbImg);
            lightboxThumbnails.appendChild(thumb);
        });
    }

    function updateLightbox() {
        if (images.length === 0) return;
        lightboxImg.src = images[currentIndex].path;
        lightboxCounter.textContent = (currentIndex + 1) + ' / ' + images.length;
        lightboxCaption.textContent = images[currentIndex].name + ' — ' + images[currentIndex].created_at;

        document.querySelectorAll('.lightbox-thumb').forEach((thumb, index) => {
            thumb.classList.toggle('active', index === currentIndex);
        });

        const activeThumb = lightboxThumbnails.children[currentIndex];
        if (activeThumb) {
            activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }

    window.openLightbox = function(index) {
        currentIndex = index;
        buildThumbnails();
        updateLightbox();
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
        stopSlideshow();
    };

    window.nextImage = function() {
        currentIndex = (currentIndex + 1) % images.length;
        updateLightbox();
    };

    window.prevImage = function() {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        updateLightbox();
    };

    window.goToImage = function(index) {
        currentIndex = index;
        updateLightbox();
    };

    window.toggleSlideshow = function() {
        if (isSlideshowPlaying) {
            stopSlideshow();
        } else {
            startSlideshow();
        }
    };

    function startSlideshow() {
        isSlideshowPlaying = true;
        lightboxPlay.innerHTML = '<i class="bi bi-pause-fill"></i>';
        slideshowInterval = setInterval(nextImage, 4000);
    }

    function stopSlideshow() {
        isSlideshowPlaying = false;
        lightboxPlay.innerHTML = '<i class="bi bi-play-fill"></i>';
        if (slideshowInterval) {
            clearInterval(slideshowInterval);
            slideshowInterval = null;
        }
    }

    lightbox.addEventListener('mouseenter', () => {
        if (isSlideshowPlaying) stopSlideshow();
    });

    lightbox.addEventListener('mouseleave', () => {
        if (isSlideshowPlaying) startSlideshow();
    });

    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
    });

    lightbox.addEventListener('wheel', function(e) {
        if (!lightbox.classList.contains('active')) return;
        e.preventDefault();
        if (e.deltaY > 0) nextImage();
        else prevImage();
    }, { passive: false });

    lightbox.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    lightbox.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const threshold = 50;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > threshold) {
            if (diff > 0) nextImage();
            else prevImage();
        }
    }

    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });
})();
</script>
@endpush
