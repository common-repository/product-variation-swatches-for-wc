const mix      = require('wp-mix');
const fsExtra  = require("fs-extra");
const path     = require("path");
const cliColor = require("cli-color");
const emojic   = require("emojic");
const min      = Mix.inProduction() ? '.min' : '';

const PackageFile = JSON.parse(File.find(Mix.paths.root('package.json')).read());

if (process.env.NODE_ENV === 'package') {

    mix.then(function () {

        let bundledir = path.basename(path.resolve(__dirname));
        let copyfrom  = path.resolve(__dirname);
        let copyto    = path.resolve(`${bundledir}`);
        // Select All file then paste on list
        let list      = `assets
woocommerce-variation-swatches.php
images
includes
languages
package.json
README.txt
uninstall.php
webpack.mix.js`;

        let includes = list.split("\n");
        fsExtra.ensureDir(copyto, function (err) {
            if (err) return console.error(err)

            includes.map(include => {

                fsExtra.copy(`${copyfrom}/${include}`, `${copyto}/${include}`, function (err) {
                    if (err) return console.error(err)

                    console.log(cliColor.white(`=> ${emojic.smiley}  ${include} copied...`));

                    /*if (include == 'assets') {
                     // Just Removed SCSS Dir
                     fsExtra.removeSync(`${copyto}/${include}/scss`);
                     }*/
                })
            });

            console.log(cliColor.white(`=> ${emojic.whiteCheckMark}  Build directory created`));
        })
    });

    return;
}

if (Mix.inProduction()) {
    mix.generatePot({
        package   : 'Product Variation Swatches for WC',
        src       : '**/*.php',
        domain    : 'woocommerce-variation-swatches',
        destFile  : `languages/woocommerce-variation-swatches.pot`
    });
}

mix.banner({
    banner : "Product Variation Swatches for WC v1.0 \n\nAuthor: Evincedev \nDate: " + new Date().toLocaleDateString('en-GB') + "\nReleased under the GPLv3 license."
});

mix.notification({
    title : 'Swatches',
    // contentImage : Mix.paths.root('images/logo.png')
});

/*if (!Mix.inProduction()) {
    mix.sourceMaps();
}*/

mix.js(`src/js/backend.js`, `assets/js/admin${min}.js`);
mix.js(`src/js/frontend.js`, `assets/js/frontend${min}.js`);
mix.js(`src/js/evince-admin.js`, `assets/js/evince-admin${min}.js`);

mix.copy(`src/js/bluebird.js`, `assets/js/bluebird${min}.js`);

// Woo Layout Injector
mix.babel(`src/js/divi_woocommerce_layout_injector.js`, `assets/js/divi_woocommerce_layout_injector${min}.js`);

mix.babel(`src/js/FormFieldDependency.js`, `assets/js/form-field-dependency${min}.js`);
mix.babel(`src/js/wp-color-picker-alpha.js`, `assets/js/wp-color-picker-alpha${min}.js`);
mix.babel(`src/js/evince-backbone-modal.js`, `assets/js/evince-backbone-modal${min}.js`);
// mix.babel(`src/js/evdpl-customize-alpha-color-control.js`, `assets/js/evdpl-customize-alpha-color-control${min}.js`);
mix.sass(`src/scss/backend.scss`, `assets/css/admin${min}.css`);

mix.sass(`src/scss/evince-admin.scss`, `assets/css/evince-admin${min}.css`);
mix.sass(`src/scss/evince-admin-notice.scss`, `assets/css/evince-admin-notice${min}.css`);

mix.sass(`src/scss/frontend.scss`, `assets/css/frontend${min}.css`);
mix.sass(`src/scss/tooltip.scss`, `assets/css/frontend-tooltip${min}.css`);
mix.sass(`src/scss/theme-override.scss`, `assets/css/evdpl-theme-override${min}.css`);
// mix.sass(`src/scss/customize-heading-control.scss`, `assets/css/evdpl-customize-heading-control${min}.css`);
