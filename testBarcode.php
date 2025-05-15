<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>test generate barcode</h1>
    <label for="">your barcode</label>
    <input type="text" name="barcode" id="barcode">
    <button type="button" onclick="genBar()">generate</button>
    <br>
    <svg id="result"></svg>    
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        function genBar(){
            var  barcode = "";
             
            //generator
            JsBarcode('#result', barcode,{
                format:"code128",
                lineColor:"#000",
                width:4,
                height:150
            } )
        }
    </script>
</body>
</html>