/*! Morphist - v3.0.0 - 2016-03-28 */!function(a){"use strict";function b(b,c){this.element=a(b),this.settings=a.extend({},d,c),this._defaults=d,this._init()}var c="Morphist",d={animateIn:"bounceIn",animateOut:"rollOut",speed:2e3,complete:a.noop};b.prototype={_init:function(){this._animationEnd="webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend",this.children=this.element.children(),this.element.addClass("morphist"),this.index=0,this.loop()},_shouldForceReflow:function(a){this.settings.animateIn===this.settings.animateOut&&a[0].offsetWidth},_animate:function(a,b,c){a.addClass("animated "+b).one(this._animationEnd,function(){c()})},loop:function(){var b=this,c=this.children.eq(this.index),d=function(){b.timeout=setTimeout(function(){c.removeClass(),b._shouldForceReflow(c),b._animate(c,"mis-out "+b.settings.animateOut,function(){b.index=++b.index%b.children.length,c.removeClass(),b.loop()})},b.settings.speed)};this._animate(c,"mis-in "+this.settings.animateIn,function(){d(),a.isFunction(b.settings.complete)&&b.settings.complete.call(b)})},stop:function(){clearTimeout(this.timeout)}},a.fn[c]=function(d){return this.each(function(){a.data(this,"plugin_"+c)||a.data(this,"plugin_"+c,new b(this,d))})}}(jQuery);

jQuery(document).ready(function ($) {
  $(".js-rotating").Morphist({
    animateIn: 'bounceIn',
    animateOut: 'fadeOutLeft',
    speed: 5000,
  });
});

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
  