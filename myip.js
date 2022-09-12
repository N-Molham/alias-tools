var https = require('https');

console.log('Loading ...');

https.get('https://api.myip.com', function (response) {
    let data = '';

    response.on('data', function (chunk) {
        data += chunk;
    });

    response.on('end', function () {
        const info = JSON.parse(data);

        console.log('IP      => ', info.ip);
        console.log('Country => ', info.country);
    });

}).on('error', function (error) {
    console.log('Error: ', error.message);
});
