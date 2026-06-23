import { shortTextSwiper, productSwiper } from '../partials/slider.js'

export default {
  init() {
    // JavaScript to be fired on all pages
  },
  finalize() {
    $(document).ready(function () {
      // @ https://stackoverflow.com/questions/48442194/javascript-increment-value-faster-with-mouse-hold
      // Navegaçãode livros
      // $book-list-size: 62px;
      // $book-list-size-mobile: 52px;
      // #book-list-translate
      let booksLength = $('.books-list').children().length
      let bookWidth = (booksLength - 7) * 62
      var temp = 0
      let timer

      const btn_right = document.getElementById('btn_right')
      const btn_left = document.getElementById('btn_left')

      // Botão direito
      btn_right.addEventListener('mousedown', rightScroll)
      btn_right.addEventListener('mouseup', timeoutClear)
      btn_right.addEventListener('mouseleave', timeoutClear)

      function rightScroll() {
        temp -= 31
        showHide(this)
        $('#book-list-translate').css('transform', `translate(${temp}px, 0)`)
        timer = setTimeout(rightScroll, 100)
      }

      // Botão esquerdo
      btn_left.addEventListener('mousedown', leftScroll)
      btn_left.addEventListener('mouseup', timeoutClear)
      btn_left.addEventListener('mouseleave', timeoutClear)

      function leftScroll() {
        temp += 31
        showHide(this)
        $('#book-list-translate').css('transform', `translate(${temp}px, 0)`)
        timer = setTimeout(leftScroll, 100)
      }

      function timeoutClear() {
        // console.log('timeout')
        clearTimeout(timer)
      }

      //Mostra/esconde botões
      $('#btn_right').css('visibility', 'hidden')
      $('#btn_right').css('pointer-events', 'none')
      function showHide(e) {
        if (temp < 31) {
          $('#btn_right').css('visibility', 'hidden')
          $('#btn_right').css('pointer-events', 'none')
          $('#book-list-translate').css('transform', `translate(0px, 0)`)
          e.preventDefault()
          e.stopPropagation();
        } else {
          $('#btn_right').css('visibility', 'visible')
          $('#btn_right').css('pointer-events', 'auto')
        }
        if (temp > bookWidth - 31) {
          $('#btn_left').css('visibility', 'hidden')
          $("#btn_left").css("pointer-events", "none");
          $('#book-list-translate').css('transform', `translate(${bookWidth}px, 0)`)
          e.preventDefault()
          e.stopPropagation();
          timeoutClear()
        } else {
          $('#btn_left').css('visibility', 'visible')
          $('#btn_left').css('pointer-events', 'auto')
        }
      }

      // const myDiv = $('#book-list-container-id')  
      const nav_container = document.getElementById('book-list-container-id')
      nav_container.addEventListener('scroll', () => {
        if (nav_container.offsetHeight + nav_container.scrollTop >= nav_container.scrollHeight) {
          console.log('scrolled to bottom')
          $('#btn_right_mobile').css('visibility', 'hidden')
        } else {
          $('#btn_right_mobile').css('visibility', 'visible')
        }
      })
    })

    // Projeto original
    shortTextSwiper()
    productSwiper()

    const banner = $('.banner')

    function menuAria() {
      const menu = $('.nav-primary')
      if (banner.hasClass('open')) {
        menu.attr('aria-hidden', 'false')
      } else {
        menu.attr('aria-hidden', 'true')
      }
    }

    $('#btn-open-menu').on('click', function () {
      banner.toggleClass('open')
      menuAria()
    })

    $(document).on('mouseup', function (e) {
      if (!banner.is(e.target) && banner.has(e.target).length === 0) {
        banner.removeClass('open')
        menuAria()
      }
    })

    $('.btn-read-more').on('click', function () {
      $(this).prev().addClass('active').end().remove()
    })
  },
}
