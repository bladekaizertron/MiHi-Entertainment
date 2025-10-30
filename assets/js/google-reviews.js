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

        reviews.forEach(review => {
            const isLiked = likedReviews.includes(review.id);
            const reviewElement = document.createElement('div');
            reviewElement.className = 'group relative bg-white/80 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-white/20 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer';
            reviewElement.setAttribute('data-review-id', review.id);
            reviewElement.onclick = () => window.open(review.google_review_url, '_blank', 'noopener,noreferrer');
            reviewElement.innerHTML = `
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-transparent to-purple-50/30 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative flex flex-col h-full">
                    <!-- Star Rating with enhanced styling -->
                    <div class="flex items-center mb-4">
                        <div class="text-yellow-400 text-lg drop-shadow-sm">${getStarRating(review.rating)}</div>
                        <div class="ml-2 w-1 h-1 bg-gray-300 rounded-full"></div>
                        <span class="ml-2 text-xs font-medium text-gray-500 uppercase tracking-wide">Verified</span>
                    </div>
                    
                    <!-- Review Text with premium typography -->
                    <div class="relative mb-6 flex-grow">
                        <div class="absolute -top-2 -left-2 text-4xl text-gray-100 font-serif leading-none select-none">"</div>
                        <p class="text-gray-700 text-sm leading-relaxed font-medium relative z-10 pl-3">${review.text}</p>
                        <div class="absolute -bottom-2 -right-2 text-4xl text-gray-100 font-serif leading-none select-none">"</div>
                    </div>
                    
                    <!-- Author and Like Section -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100/60">
                        <div class="flex items-center">
                            <div class="relative">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full blur-sm opacity-20"></div>
                                <img src="${review.profile_photo_url}" 
                                     alt="${review.author_name}" 
                                     class="relative w-10 h-10 rounded-full object-cover border-2 border-white shadow-md">
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-100">
                                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="flex items-center">
                                    <h4 class="font-semibold text-gray-900 text-sm">${review.author_name}</h4>
                                    <svg class="w-3 h-3 ml-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">${formatDate(review.time * 1000)}</p>
                            </div>
                        </div>
                        
                        <!-- Premium Like Button -->
                        <button class="like-button group/like relative flex items-center space-x-1 px-3 py-2 rounded-full transition-all duration-300 ${isLiked ? 'bg-gradient-to-r from-red-50 to-pink-50 border border-red-200' : 'bg-gray-50/80 border border-gray-200/60 hover:bg-gradient-to-r hover:from-gray-50 hover:to-gray-100'}" 
                                onclick="event.stopPropagation(); toggleLike('${review.id}')">
                            <div class="relative">
                                <svg class="w-4 h-4 transition-all duration-300 ${isLiked ? 'text-red-500 scale-110' : 'text-gray-400 group-hover/like:text-red-400 group-hover/like:scale-110'}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                </svg>
                                ${isLiked ? '<div class="absolute inset-0 bg-red-200 rounded-full blur-sm opacity-50"></div>' : ''}
                            </div>
                            <span class="like-count text-xs font-semibold ${isLiked ? 'text-red-600' : 'text-gray-600'}">${review.likes}</span>
                        </button>
                    </div>
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