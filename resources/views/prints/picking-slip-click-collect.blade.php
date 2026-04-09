<html>
    <head>
        <title>Click & Collect Order</title>
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
               

                

                .contents .header{
                    color: grey ;
                }

                @top-center {
                    content: normal !important;
                }

                .footer {
                    page-break-after: always;
                    margin-bottom: 5em;
                }
                @page {
                    margin-top: 0.5cm; /* Adjust the margin-top value as needed */
                    /* margin-left: 1cm; 
                    margin-right: 1cm;  */
                    size: 4.1in 5.8in; /* Set custom dimensions for 4r size */
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
                        <p>{{ $data['info']->invoiceState == "FULFILLED" ? "PAID" : "UNPAID"}}</p>
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
                    <td width="25%" colspan="2" style="text-align: center;">
                        <!-- <img src="http://psw.synccare.com.au/psw_logo.jpg" width="120"> -->
                        <img src="http://psw.synccare.com.au/psw-uniform1.jpg" width="220">
                    </td>
                     
                  
                    <td width="30%" colspan="2">
                        <div class="container" style="border: 1px dashed grey;padding: 10px;text-align: center; ">
                        <span>{{ date("d.m.Y") }} &nbsp;&nbsp;{{ date("H:i:s") }}</span><br>
                        
                        </div>
                    </td>

                </tr>
                <tr >
                    
                    <td width="100%" colspan="4" style="text-align:center">
                        <div class="container" style="border: 1px dashed grey;padding: 2px;text-align: center;display: flex;justify-content: center;margin-top:-5px;">

                         Click & Collect Order 

                        </div>
                        <div class="container" style="border: 1px dashed grey;padding: 5px;text-align: center;display: flex;justify-content: center;margin-top:-1px;">

                        {{ $data['schoolInfo']->SchoolName }}

                        </div>
                    </td>

                   
                </tr>

                

                <tr >
                    <td width="25%" colspan="2" style="text-align:center">
                        <div class="container" style="border: 1px dashed grey;padding: 2px;text-align: center;margin-top:-5px;">
                             {{$data['client']->fullName }} 
                             
                        </div>
                        
                    </td>
                    
                    <td   colspan="2" style="text-align:center">
                        <div class="container" style="border: 1px dashed grey;padding: 2px;text-align: center;margin-left:-5px;margin-top:-5px;">
                             {{ $data["info"]["number"] }} 
                             
                        </div>
                        
                    </td>

 

                </tr>

                @if($data['client']->phone || $data['client']->mobile || $data['client']->email)
                    <tr >

                        <td  colspan="4"  >
                            <div class="container" style="border: 1px dashed grey;padding: 2px;text-align: center;margin-top:-5px;">
                                <span> {{ $data['client']->phone ? $data['client']->phone : $data['client']->mobile }} </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <span>  {{ $data['client']->email }} </span>
                                
                            </div>
                            
                        </td> 

                    </tr>
                @endif

                <tr>
                    <td colspan="4">
                        <div class="container" style="border: 1px dashed grey;padding: 3px;text-align: center;display: flex;justify-content: center;margin-top:-5px;">
                            
                        
                            {!! DNS1D::getBarcodeSVG((string)$data["info"]["number"], 'C128',2.5,30,'black', false) !!}  
                            
                             
                        </div>
                    </td>
                </tr>

                  
            </tbody>
        </table>

        <table class="contents"  >
            <tbody>
                  
                @foreach($data["productDetails"] as $p)
                <tr>
                    <td width="25%">
                        <div class="container" style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px ">
                             {{ $p['itemID']  }}
                           
                        </div> 
                    </td>
                    <td width="5%" colspan="3">
                        <div class="container" style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px">
                        {{ substr($p['productName2'], 0, 25) }}
                           
                        </div>
                    </td>
                    
                    <td width="10%">
                        <div class=" " style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px">

                        H
                       
                        </div>
                    </td>
                    <td width="10%" rowspan="2">
                        <div class=" " style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>&nbsp;

                        </div>
                    </td>
                </tr>

                <tr>
                    <td width="25%">
                        <div class="container" style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px ">
                             {{ $p['configID']  }}
                            
                        </div> 
                    </td>
                    <td width="5%">
                        <div class="container" style="border: 1px dashed grey;padding: 1px;text-align: center; font-size:13px">
                        {{ $p['colourID'] }}
                           
                        </div>
                    </td>
                    <td width="20%"  > 
                        
                        <div class="container" style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px ">
                            {{ $p['Size'] }}
                           
                        </div>
                    </td>

                    <td width="30%"  > 
                        <div class=" " style="border: 1px dashed grey;padding: 1px;text-align: center;font-size:13px">
                         
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
                    <td width="15%">
                        <div class=" " style="border: 1px solid grey;padding: 1px;text-align: center;font-size:13px">
                         
                            {{ (int)$p['qty'] }} 
                        </div>
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