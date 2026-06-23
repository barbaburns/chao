import Swiper from 'swiper/bundle';

function togglePaginationBullets(swiper) {
  if (!swiper || !swiper.pagination || !swiper.pagination.el) {
    return;
  }

  var pagination = swiper.pagination.el;
  var bullets = pagination.querySelectorAll('.swiper-pagination-bullet');

  pagination.classList.toggle('hidden', bullets.length < 2);
}

function withBulletVisibility(options) {
  var swiperEvents = options.on || {};

  return Object.assign({}, options, {
    on: Object.assign({}, swiperEvents, {
      init: function(swiper) {
        togglePaginationBullets(swiper);

        if (typeof swiperEvents.init === 'function') {
          swiperEvents.init(swiper);
        }
      },
      breakpoint: function(swiper) {
        togglePaginationBullets(swiper);

        if (typeof swiperEvents.breakpoint === 'function') {
          swiperEvents.breakpoint(swiper);
        }
      },
      paginationUpdate: function(swiper) {
        togglePaginationBullets(swiper);

        if (typeof swiperEvents.paginationUpdate === 'function') {
          swiperEvents.paginationUpdate(swiper);
        }
      },
      update: function(swiper) {
        togglePaginationBullets(swiper);

        if (typeof swiperEvents.update === 'function') {
          swiperEvents.update(swiper);
        }
      },
    }),
  });
}

var swiperAutores = new Swiper('#swiper-autores', withBulletVisibility({
  autoplay: false,
  speed: 500,

  breakpoints: {
    320: {
      slidesPerView: 2,
      slidesPerGroup: 2,
      spaceBetween: 17,
    },
    540: {
      slidesPerView: 3,
      slidesPerGroup: 3,
      spaceBetween: 34,
    },
    780: {
      slidesPerView: 4,
      slidesPerGroup: 4,
      spaceBetween: 88,
    },
  },
  pagination: {
    el: '.swiper-pagination',
    type: 'bullets',
    clickable: true,
  },
}));

function shortTextSwiper() {
  if ($('#swiper-short-texts').length) {
    var shortTextSwiper = new Swiper('#swiper-short-texts', {
      speed: 500,
      loop: true,
      autoplay: {
        delay: 5000,
      },
    });
    console.log(shortTextSwiper);
  }
}

function productSwiper() {
  if ($('.swiper-product').length) {
    $('.swiper-product').each(function(index, element){
      var instance = $(this).data('instance');
      index = new Swiper(element, withBulletVisibility({
        autoplay: false,
        speed: 500,
        navigation: {
          nextEl: '.swiper-button-next-' + instance,
          prevEl: '.swiper-button-prev-' + instance,
        },
        pagination: {
          el: '.swiper-pagination-' + instance,
          type: 'bullets',
          clickable: true,
        },
      }));
      //console.log(index);
    });
  }
}

function homeProductsSwiper() {
  if ($('#swiper-product-home').length) {
    var swiperProductHome = new Swiper('#swiper-product-home', withBulletVisibility({
      autoplay: false,
      speed: 500,
      navigation: {
        nextEl: '.swiper-button-next-product-home',
        prevEl: '.swiper-button-prev-product-home',
      },
      pagination: {
        el: '.swiper-pagination-product-home',
        type: 'bullets',
        clickable: true,
      },
    }));
    //console.log(swiperProductHome);
  }
}

function relatedProductsSwiper() {
  if ($('#swiper-related-products').length) {
    var swiperRelatedProducts = new Swiper('#swiper-related-products', withBulletVisibility({
      autoplay: false,
      speed: 500,

      breakpoints: {
        320: {
          slidesPerView: 2,
          spaceBetween: 17,
        },
        576: {
          slidesPerView: 3,
          spaceBetween: 34,
        },
        992: {
          slidesPerView: 4,
          spaceBetween: 88,
        },
      },
      pagination: {
        el: '.swiper-pagination-related-products',
        type: 'bullets',
        clickable: true,
      },
    }));
  }
}

function productManuscriptSwiper() {
  if (!$('.product-manuscript-slider').length) {
    return;
  }

  new Swiper('.product-manuscript-slider', withBulletVisibility({
    autoplay: false,
    speed: 500,
    pagination: {
      el: '.swiper-pagination-product-manuscript',
      type: 'bullets',
      clickable: true,
    },
  }));
}

export { shortTextSwiper, productSwiper, homeProductsSwiper, relatedProductsSwiper, productManuscriptSwiper }
