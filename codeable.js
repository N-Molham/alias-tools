var estimate = Math.abs( parseFloat( process.argv[2] ) );
if ( isNaN( estimate ) ) {
	// skip
	console.log( 'Invalid estimate input' );
	process.exit();
}

Number.prototype.formatMoney = function(c, d, t){
var n = this, 
    c = isNaN(c = Math.abs(c)) ? 2 : c, 
    d = d == undefined ? "." : d, 
    t = t == undefined ? "," : t, 
    s = n < 0 ? "-" : "", 
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
    j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
 };

console.log( "Client's amount: $" + ( estimate + ( estimate * 0.15 ) ).formatMoney() );
console.log( "Earned amount:   $" + ( estimate - ( estimate * 0.10 ) ).formatMoney() );