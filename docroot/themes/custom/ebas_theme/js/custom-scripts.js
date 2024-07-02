// Script for navigation bar
const bar = document.getElementById('bar');
const close= document.getElementById('close');
const nav= document.getElementById('navbar');

if (bar){
    bar.addEventListener('click',() => {
        nav.classList.add('active');
    });
}

if (close){
    close.addEventListener('click',() => {
        nav.classList.remove('active');
    });
}

(function (Drupal, once) {
    Drupal.behaviors.initializeSwiper = {
      attach: function (context, settings) {
        var mySwiper = new Swiper('.swiper-container', {
          loop: false, // Disable loop mode if there are not enough slides
          autoplay: {
            delay: 8000,
          },
          navigation: {
            nextEl: '.carousel_control-next',
            prevEl: '.carousel_control-prev',
          },
          pagination: {
            el: '.swiper-pagination',
            clickable: true,
          },
          slidesPerView: 'auto',
          spaceBetween: '100%',
       // Adjust the space between slides as needed
        });
  
        // Add click event listeners to custom buttons if needed
        once('swiper', context.querySelectorAll('.carousel_control-next')).forEach(function (element) {
          element.addEventListener('click', function () {
            mySwiper.slideNext();
          });
        });
  
        once('swiper', context.querySelectorAll('.carousel_control-prev')).forEach(function (element) {
          element.addEventListener('click', function () {
            mySwiper.slidePrev();
          });
        });
  
        // Example: If you want to navigate to a specific slide on button click
        once('swiper', context.querySelectorAll('.custom-button')).forEach(function (element) {
          element.addEventListener('click', function () {
            mySwiper.slideTo(1); // Go to the second slide (index starts from 0)
          });
        });
      }
    };
  })(Drupal, once);
  