// Google Reviews with Sample Data
document.addEventListener('DOMContentLoaded', function() {
    const reviewsContainer = document.getElementById('google-reviews');
    const averageRatingElement = document.querySelector('.average-rating');
    const totalRatingsElement = document.querySelector('.total-ratings');
    
    // Sample reviews data with Google review links
    const sampleReviews = [
        {
            id: 'review-1',
            author_name: 'Tricia O Mahen Dickey',
            rating: 5,
            text: 'The custom photobooth with mosaic wall was a great way to pull through our conference theme, elevate the attendee experience and give our clients a memorable keepsake. I appreciated all of the options available to meet our specific event needs and the staff was wonderful. Highly recommend!',
            time: Date.now() / 1000 - 1551400, // 21 days ago
            profile_photo_url: 'https://ui-avatars.com/api/?name=Tricia+OMahenDickey&background=10b981&color=fff&size=40',
            likes: 12,
            google_review_url: 'https://share.google/EMqHh1vCYGNhxLVAm'
        },
        {
            id: 'review-2',
            author_name: 'Ashlee Wagoner',
            rating: 5,
            text: 'We were so pleased to have MiHi at our wedding with their photobooth set up! All our guests loved it, it was a huge hit. The attendant was fabulous as well :)',
            time: Date.now() / 1000 - 2592000, // 1 month ago
            profile_photo_url: 'https://ui-avatars.com/api/?name=Ashlee+Wagoner&background=3b82f6&color=fff&size=40',
            likes: 8,
            google_review_url: 'https://share.google/dap2POr5QmSmRQqk9'
        },
        {
            id: 'review-3',
            author_name: 'Jill Nord',
            rating: 5,
            text: 'If I could give more than 5 stars I definitely would!!! Mihi was such a positive addition to our daughter’s wedding! The guests loved it, the attendants were soooo friendly and accommodating, the pics came out quickly and were such great quality!  Absolutely fabulous experience all the way around! There were a few pics of the bride and groom that are totally worthy of hanging on the wall as “the” wedding photo!! Thanks so much!!',
            time: Date.now() / 1000 - 22000, // 1 month ago
            profile_photo_url: 'https://ui-avatars.com/api/?name=Jill+Nord&background=14b8a6&color=fff&size=40',
            likes: 15,
            google_review_url: 'https://share.google/4EXdoGWGa4as3rYw6'
        },
    ];

    // Function to display stars based on rating
    function getStarRating(rating) {
        const fullStar = '★';
        const emptyStar = '☆';
        return fullStar.repeat(Math.round(rating)) + emptyStar.repeat(5 - Math.round(rating));
    }

    // Format date
    function formatDate(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }

    // Get liked reviews from localStorage
    function getLikedReviews() {
        const liked = localStorage.getItem('mihi-liked-reviews');
        return liked ? JSON.parse(liked) : [];
    }

    // Save liked reviews to localStorage
    function saveLikedReviews(likedReviews) {
        localStorage.setItem('mihi-liked-reviews', JSON.stringify(likedReviews));
    }

    // Toggle like for a review
    function toggleLike(reviewId) {
        const likedReviews = getLikedReviews();
        const isLiked = likedReviews.includes(reviewId);
        
        if (isLiked) {
            // Remove like
            const updatedLikes = likedReviews.filter(id => id !== reviewId);
            saveLikedReviews(updatedLikes);
        } else {
            // Add like
            likedReviews.push(reviewId);
            saveLikedReviews(likedReviews);
        }
        
        // Update the like button appearance
        updateLikeButton(reviewId, !isLiked);
    }

    // Update like button appearance
    function updateLikeButton(reviewId, isLiked) {
        const likeButton = document.querySelector(`[data-review-id="${reviewId}"] .like-button`);
        const likeIcon = likeButton.querySelector('svg');
        const likeCount = likeButton.querySelector('.like-count');
        
        if (isLiked) {
            likeButton.classList.add('liked');
            likeIcon.classList.add('text-red-500');
            likeIcon.classList.remove('text-gray-400');
            likeCount.textContent = parseInt(likeCount.textContent) + 1;
        } else {
            likeButton.classList.remove('liked');
            likeIcon.classList.remove('text-red-500');
            likeIcon.classList.add('text-gray-400');
            likeCount.textContent = parseInt(likeCount.textContent) - 1;
        }
    }

    // Display reviews
    function displayReviews(reviews) {
        reviewsContainer.innerHTML = ''; // Clear existing content
        
        if (!reviews || reviews.length === 0) {
            reviewsContainer.innerHTML = '<p class="text-gray-600 text-center py-4">No reviews found.</p>';
            return;
        }

        const likedReviews = getLikedReviews();

        // Shadow colors for each card (matching CTA style but adapted for white background)
        const shadowColors = [
            'rgba(236,72,153,0.15)',  // pink
            'rgba(59,130,246,0.15)',  // blue
            'rgba(14,165,233,0.15)'   // cyan
        ];

        reviews.forEach((review, index) => {
            const isLiked = likedReviews.includes(review.id);
            const reviewElement = document.createElement('div');
            // Styled to match CTA cards but adapted for white background
            const shadowColor = shadowColors[index % shadowColors.length];
            reviewElement.className = 'bg-white border border-gray-200/60 rounded-3xl p-6 transition-all duration-300 hover:-translate-y-1';
            reviewElement.style.boxShadow = `0 20px 45px -20px ${shadowColor}`;
            reviewElement.addEventListener('mouseenter', function() {
                this.style.boxShadow = `0 25px 55px -20px ${shadowColor}`;
            });
            reviewElement.addEventListener('mouseleave', function() {
                this.style.boxShadow = `0 20px 45px -20px ${shadowColor}`;
            });
            reviewElement.setAttribute('data-review-id', review.id);
            reviewElement.innerHTML = `
                <div class="flex flex-col h-full">
                    <!-- Author Info -->
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="text-gray-900 font-semibold text-base">${review.author_name}</h4>
                        </div>
                    </div>
                    
                    <!-- Star Rating -->
                    <div class="flex items-center mb-4">
                        <div class="flex items-center text-yellow-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Review Text -->
                    <p class="text-gray-600 text-sm leading-relaxed mb-6 flex-grow">${review.text}</p>
                    
                    <!-- Google Review Link -->
                    <a href="${review.google_review_url}" target="_blank" rel="noopener noreferrer" 
                       class="inline-flex items-center gap-2 text-sm font-medium text-[#0050ff] hover:text-[#0040d9] transition-colors mt-auto">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>View on Google</span>
                    </a>
                </div>
            `;
            reviewsContainer.appendChild(reviewElement);
        });
    }

    // Calculate and update average rating
    function updateAverageRating(reviews) {
        if (averageRatingElement) {
            // Set to 4.9 as shown in the Google reviews
            averageRatingElement.textContent = '4.9';
        }
        
        if (totalRatingsElement) {
            // Set to 156 as shown in the Google reviews
            totalRatingsElement.textContent = '194';
        }
    }

    // Initialize
    function init() {
        // Sort by most recent first and limit to 3
        const sortedReviews = [...sampleReviews].sort((a, b) => b.time - a.time);
        const limited = sortedReviews.slice(0, 3);
        displayReviews(limited);
        updateAverageRating(sortedReviews);
    }

    // Make toggleLike globally accessible
    window.toggleLike = toggleLike;

    // Initialize the reviews
    init();
});