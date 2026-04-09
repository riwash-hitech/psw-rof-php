<html>
    <head>
        <title>Picking Slip</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Arial">
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Noto+Sans+Mono"> -->
        <style>
            body {
                font-family: 'Arial', sans-serif;
                /* font-family: 'Noto Sans Mono', monospace; */
                -webkit-touch-callout: none;
                -webkit-user-select: none;
                -khtml-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
            }
            @page {
                size: auto;
                margin: 0mm;
            }
            @media print {
                hr.print-line {
                    display: block;
                    border: none;
                    height: 1px;
                    background-color: black;
                }

                body{
                    padding:20px;
                }

                .contents .header{
                    color: grey ;
                }

                @top-center {
                    content: normal !important;
                }

            }


           
            .div-inline {
                display: inline-block;
                border: 1px solid black;
                padding: 1px;
                text-align: center;
                width: 50px;
            }
            /* .div-inline1 {
                display: inline-block;
                border: 1px dashed grey;
                padding: 1px;
                text-align: center;
                width: 50%;
            } */

        </style>
    </head>
    <body style="text-align: center;" onload="window.print()" onfocus="window.close()">
        <table>
            <tbody>
                <tr style="width:100%">
                    <td width="25%">
                        <img src="http://psw.synccare.com.au/psw_logo.jpg" width="120">
                    </td>
                    <td width="40%">
                        <h1>Picking Slip</h1>
                    </td>
                    <td width="5%">
                        <h6>PRINTED</h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 10px;text-align: center;">
                            <span>{{ date("l jS \of F Y") }}</span><br>
                            <span>{{ date('H:i:s') }}</span>
                        </div>
                    </td>

                </tr>
                <tr >
                    <td width="25%">
                        <p> Store ID : {{ $axWarehouse->LocationID }}</p>
                    </td>
                    <td width="40%">
                        <p	style="font-size: 16px;">{{ $axWarehouse->LocationName }}</p>
                    </td>
                    <td width="5%">
                        <h6></h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 3px;text-align: center;display: flex;justify-content: center;">
                        
                       
                         {!! DNS1D::getBarcodeSVG((string)$data["number"], 'C128',1.3,25,'black', false) !!}  
                            
                            <!-- <span>Order Number - 12345</span> -->
                        </div>
                    </td>

                </tr>

                <tr >
                    <td width="25%" colspan="2">
                        <p> School : {{ $schoolInfo->SchoolName }}</p>
                    </td>
                    <!-- <td width="40%">
                        <p	style="font-size: 16px;"></p>
                    </td> -->
                    <td width="5%">
                        <h6></h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 3px;text-align: center;">
                            <!-- <span>BARCODE</span><br> -->
                            <span>Order Number - {{ $data["number"] }}</span>
                        </div>
                    </td>

                </tr>

                <tr >
                    <td width="25%" colspan="3">
                        <p> Customer Ref :  </p>
                    </td>

                    <td width="30%">
                        <!-- <div class="container" style="border: 1px solid black;padding: 3px;text-align: center;"> -->
                            <!-- <span>BARCODE</span><br> -->
                            <span>Picker Name : </span>
                        <!-- </div> -->
                    </td>

                </tr>

                <tr >
                    <td width="25%" colspan="3">
                        <p> Customer Name :  {{$client->fullName }}</p>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 10px;text-align: center;">
                            <!-- <span>BARCODE</span><br> -->
                            <!-- <span>Picker Name : </span> -->
                        </div>
                    </td>

                </tr>

                <tr>
                    <td colspan="4"> 
                        <div class="container" style="border-top: 1px solid black;padding: 0px;text-align: left;text-decoration: underline;font-size: 16px;font-weight: bold;height: 1px;">
                            
                       </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="3"> 
                        <div class="container" style="border: 1px solid black;padding: 1px;text-align: left;text-decoration: underline;font-size: 16px;font-weight: bold;">
                             Items To Be Supplied TODAY 
                            <!-- <span>BARCODE</span><br> -->
                            <!-- <span>Picker Name : </span> -->
                        </div>
                    </td>

                    <td > 
                         <input type="checkbox" /> <label>Back Order</label>
                    </td>

                </tr>

            </tbody>
        </table>

        <table class="contents">
            </tbody>
                <tr class="header" style="width:100%">
                    <td width="25%">
                        Item ColourID Config
                    </td>
                    <td width="5%">
                        Size
                    </td>
                    <td width="30%">
                        Product Name
                    </td>

                    <td width="20%">
                        Qty/Picked
                    </td>
                    <td width="15%">
                        Location/s
                    </td>
                    <td width="5%">
                        SOH
                    </td>
                </tr>
                
                @foreach($productDetails as $p)
                <tr>
                    <td width="25%">
                        <div class="container" style="border: 1px solid black;padding: 1px;text-align: center;">
                             {{ $p['ICSC']  }}
                            <!-- <span>BARCODE</span><br> -->
                            <!-- <span>Picker Name : </span> -->
                        </div> 
                    </td>
                    <td width="5%">
                        <div class="container" style="border: 1px solid black;padding: 1px;text-align: center;">
                        {{ $p['Size'] }}
                            <!-- <span>BARCODE</span><br> -->
                            <!-- <span>Picker Name : </span> -->
                        </div>
                    </td>
                    <td width="30%" rowspan="2" style="vertical-align: top;text-align: left;"> 
                        {{ $p['productName'] }}
                    </td>

                    <td width="20%"  > 
                        <div class="div-inline">
                        {{ (int)$p['qty'] }} 
                          </div>
                          <div class="div-inline" style="border: 1px dashed grey !important;">
                                 &nbsp;
                          </div>
                        
                    </td>
                    <td width="15%">
                        <div class=" " style="border: 1px solid black;padding: 1px;text-align: center;">
                         
                        @if(@$p['location'])
                            @if($p['location'] == "DEF")
                                &nbsp;
                            @else
                                {{ @$p['location'] }}
                            @endif
                        @else
                            &nbsp;
                        @endif
                        </div>
                    </td>
                    <td width="5%">
                        <div class=" " style="border: 1px solid black;padding: 1px;text-align: center;">
                            @if(@$p["SOH"])
                                {{ @$p["SOH"] }}
                            @else
                                &nbsp;
                            @endif
                            

                        </div>
                    </td>
                </tr>

                <tr>
                    <td width="25%" colspan="2">
                        <div class="container" style="border: 0px solid black;padding: 1px;text-align: left;">
                        {!! DNS1D::getBarcodeSVG((string)$p['ICSCBarcode'], 'C128',1.3,25,'black', false); !!} 
                        </div> 
                    </td>
                     
                    <td width="15%" colspan="3" style="text-align: center;">
                         Bulk Qty : 0
                    </td>
                     
                </tr>

                @endforeach
                 
  

                <td colspan="6"> 
                    <div class="container" style="border-top: 1px solid black;padding: 0px;text-align: left;text-decoration: underline;font-size: 16px;font-weight: bold;height: 1px;">
                        
                   </div>
                </td>

                <tr>
                    <td  colspan="3" style="text-align: right;">
                           Cross Check Total Qty: 
                    </td>
                    

                    <td width="20%"  > 
                        <div class="div-inline">
                              {{ $totQty }}
                          </div>
                          <div class="div-inline" style="border: 1px dashed grey !important">
                             &nbsp; 
                          </div>
                        
                    </td>
                    
                </tr>

                

                
            </tbody>
        </table>



    </body>
</html>
<script>
document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Disable inspecting and right-click on document
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F12' || (e.ctrlKey && e.key === 'Shift' && e.key === 'I')) {
                e.preventDefault();
            }
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'I') {
                e.preventDefault();
            }
        });
  </script>