// import external dependencies
import 'jquery';

// Import everything from autoload
import './autoload/**/*'

import { library, dom } from '@fortawesome/fontawesome-svg-core';
import { faFacebookSquare, faInstagram, faTwitterSquare, faLinkedin, faYoutube } from '@fortawesome/free-brands-svg-icons';

library.add(faFacebookSquare, faInstagram, faTwitterSquare, faLinkedin, faYoutube);
dom.watch();

// import local dependencies
import Router from './util/Router';
import common from './routes/common';
import home from './routes/home';
import singleProduct from './routes/product';
import singleManuscrito from './routes/single-manuscrito';

/** Populate Router instance with DOM routes */
const routes = new Router({
  common,
  home,
  singleProduct,
  singleManuscrito,
});

// Load Events
jQuery(document).ready(() => routes.loadEvents());

