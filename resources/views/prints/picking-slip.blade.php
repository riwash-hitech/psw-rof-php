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
            @if($isA4 == 0)
                @media print {
                @page {
                    size: 4in 6in; /* Set custom dimensions for 4r size */
                    margin: 0; /* Optional: Set margin to 0 for full-page printing */
                }
                }
            @endif

            
            @media print {
                hr.print-line {
                    display: block;
                    border: none;
                    height: 1px;
                    background-color: black;
                }
               

                

                .contents .header{
                    color: grey ;
                }

                @top-center {
                    content: normal !important;
                }

                .footer {
                    page-break-after: always;
                    margin-bottom: 10em;
                }
                @page {
                    margin-top: 0.5cm; /* Adjust the margin-top value as needed */
                    margin-left: 1cm; 
                    margin-right: 1cm; 
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
    @foreach($bulkPickingSlip as $data)   
    <table  >
            <tbody>
                <tr >
                    <td width="25%">
                        <p>{{ $data['info']->invoiceState == "FULFILLED" ? "PAID" : "UNPAID" }}</p>
                    </td>
                    <td width="40%">
                        
                    </td>
                    <td width="5%">
                        <h6></h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid white;padding: 3px;text-align: center;display: flex;justify-content: center;">

                            <span>{{ $data['info']->isExpress == 1 ? "EXPRESS" : '' }}</span>

                        </div>
                    </td>

                </tr>
                <tr style="width:100%">
                    <td width="35%" style="text-align:center;">
                    <!-- <img src="http://psw.synccare.com.au/psw_logo.jpg" width="{{ $data["info"]->paperSize == 1 ? 120 : 80 }}"> -->
                    <img src="http://psw.synccare.com.au/psw-uniform1.jpg" width="220">
                        
                    </td>
                    <td width="30%">
                        <h1 style="font-size:{{ $data["info"]->paperSize == 1 ? 34 : 20 }}">Picking Slip</h1>
                    </td>
                    <td width="5%">
                        <h6>PRINTED</h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 10px;text-align: center;">
                            <span style="font-size:{{ $data["info"]->paperSize == 1 ? 14 : 10 }}">{{ date("l jS \of F Y") }}</span><br>
                            <span style="font-size:{{ $data["info"]->paperSize == 1 ? 14 : 12 }}">{{ date('H:i:s') }}</span>
                        </div>
                    </td>

                </tr>
                <tr >
                    <td width="25%">
                        <p style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}"> Store ID : {{ $data['axWarehouse']->LocationID }}</p>
                    </td>
                    <td width="40%">
                        <p	style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">{{ $data['axWarehouse']->LocationName }}</p>
                    </td>
                    <td width="5%">
                        <h6></h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 3px;text-align: center;display: flex;justify-content: center;">
                        
                       
                         {!! DNS1D::getBarcodeSVG((string)$data["info"]["number"], 'C128',1.3,25,'black', false) !!}  
                            
                            <!-- <span>Order Number - 12345</span> -->
                        </div>
                    </td>

                </tr>

                <tr >
                    <td width="25%" colspan="2">
                        <p style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}"> School : {{ $data['schoolInfo']->SchoolName }}</p>
                    </td>
                    <!-- <td width="40%">
                        <p	style="font-size: 16px;"></p>
                    </td> -->
                    <td width="5%">
                        <h6></h6>
                    </td>

                    <td width="30%">
                        <div class="container" style="border: 1px solid black;padding: 3px;text-align: center;font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                            <!-- <span>BARCODE</span><br> -->
                            <span>Order Number - {{ $data["info"]["number"] }}</span>
                        </div>
                    </td>

                </tr>

                <tr >
                    <td width="25%" colspan="3">
                        <p style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}"> Customer Ref :  </p>
                    </td>

                    <td width="30%">
                        <!-- <div class="container" style="border: 1px solid black;padding: 3px;text-align: center;"> -->
                            <!-- <span>BARCODE</span><br> -->
                            <span style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">Picker Name : </span>
                        <!-- </div> -->
                    </td>

                </tr>

                <tr >
                    <td width="25%" colspan="3">
                        <p style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}"> Customer Name :  {{$data['client']->fullName }}</p>
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

        <table class="contents"  >
            </tbody>
                <tr class="header" style="width:100%">
                    <td width="25%" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        Item ColourID Config
                    </td>
                    <td width="5%" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        Size
                    </td>
                    <td width="30%" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        Product Name
                    </td>

                    <td width="20%" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        Qty/Picked
                    </td>
                    <td width="15%" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        Location/s
                    </td>
                    <td width="5%" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        SOH
                    </td>
                </tr>
                
                @foreach($data["productDetails"] as $p)
                <tr>
                    <td width="25%">
                        <div class="container" style="border: 1px solid black;padding: 1px;text-align: center;font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                             {{ $p['ICSC']  }}
                            <!-- <span>BARCODE</span><br> -->
                            <!-- <span>Picker Name : </span> -->
                        </div> 
                    </td>
                    <td width="5%">
                        <div class="container" style="border: 1px solid black;padding: 1px;text-align: center;font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
                        {{ $p['Size'] }}
                            <!-- <span>BARCODE</span><br> -->
                            <!-- <span>Picker Name : </span> -->
                        </div>
                    </td>
                    <td width="30%" rowspan="2" style="vertical-align: top;text-align: left;font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}"> 
                        {{ $p['productName'] }}
                    </td>

                    <td width="20%"  > 
                        <div class="div-inline" style="font-size:{{ $data["info"]->paperSize == 1 ? 16 : 12 }}">
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
                        {!! DNS1D::getBarcodeSVG((string)$p['barcode'], 'C128',1.3,25,'black', false); !!} 
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

                <tr class="footer">
                    <td  colspan="3" style="text-align: right;">
                           Cross Check Total Qty: 
                    </td>
                    

                    <td width="20%"  > 
                        <div class="div-inline">
                              {{ $data["totQty"] }}
                          </div>
                          <div class="div-inline" style="border: 1px dashed grey !important">
                             &nbsp; 
                          </div>
                        
                    </td>
                    
                </tr>

                

                
            </tbody>
        </table>

    @endforeach

    </body>
</html>
<script>
/* document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        }); */

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