<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Summary</title>
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap');@import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap');
  </style>
</head>

<body style="margin:0; padding:0; font-family:  Figtree, Arial, sans-serif; background-color:#ffffff;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
          <tr>
            <td style="padding: 40px 20px 20px 20px;">
              <img src="{{ asset('public/logo.png') }}" alt="Logo" style="display:block;">
            </td>
          </tr>
          <tr style="display: flex;justify-content: left;align-items: center;">
            <td style="padding:20px;">
              <img src="{{ asset('public/confirm-order.svg') }}" alt="confirm_order" style="display: block;">
          </td>
          <td>
            <p style="margin:0; font-size:14px; color:#333333;font-weight: 300;padding-top:15px;">ORDER #GRNB-{{ $order->id }} </p>
            <p style="margin:5px 0 15px 0; font-size:18px; color:rgba(45, 52, 31, 1);"><strong>THANK YOU, {{ $user->name }}</strong></p>
          </td>
          </tr>
          <tr>
            <td style="padding-left: 20px;">
              <p style="font-size:14px; color:#666666;">Thanks for farming with Hi B Greenbox, your order #10892, has been confirmed and currently being processed:</p>
            </td>
          </tr>

          <tr>
            <td style="padding:20px; background-color:#f9f9f9; border-radius:8px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td colspan="2" style="font-size:16px; font-weight:bold; padding-bottom:10px;">ORDER DETAILS</td>
                </tr>
                @foreach ($order->items as $item)
                    <tr>
                      <td style="padding:10px 0; font-size:14px;display: flex;align-items: center;">
                        <img src="{{ asset('public/' . $item->product->images[0]) }}" alt="logo" style="border-radius: 50%;width: 65px;height: 65px;object-fit: contain;padding-right: 6px;">
                        {{ $item->name }}
                      </td>
                      <td style="text-align:right; font-size:14px; padding:10px 0;"><strong>&#8358;{{ number_format($item->price * $item->quantity, 2) }} </strong></td>
                    </tr>
                @endforeach


                <tr>
                  <td colspan="2" style="border-top:1px solid #dddddd; padding-top:10px;"></td>
                </tr>
                <tr>
                  <td style="padding:10px 0; font-size:14px; font-weight:bold;">SUBTOTAL:</td>
                  <td style="text-align:right; font-size:14px;"><strong>&#8358; {{ number_format($order->sub_total, 2) }} </strong></td>
                </tr>
                <tr>
                  <td style="padding:10px 0; font-size:14px; font-weight:bold;">SHIPPING:</td>
                  <td style="text-align:right; font-size:14px;">{{ ucfirst($order->type) }} <strong>&#8358; {{ number_format($order->total_shipping_fee, 2) }} </strong></td>
                </tr>
                <tr>
                  <td style="padding:10px 0; font-size:14px; font-weight:bold;">PAYMENT METHOD:</td>
                  <td style="text-align:right; font-size:14px;">{{ ucfirst($order->payment_method) }}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0; font-size:16px; font-weight:bold;">TOTAL:</td>
                  <td style="text-align:right; font-size:16px; font-weight:bold;"><strong>&#8358; {{ number_format($order->total, 2) }} </strong></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr style="margin-top: 30px;">
            <td style="padding:20px;border: 1px solid rgba(45, 52, 31, 0.2);margin-top: 15px;border-radius: 4px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size: 16px;padding: 10px;"><strong>CUSTOMER INFORMATION</strong></td>
                </tr>
                <tr>
                  <td style="padding:10px;">
                    <p style="margin:0; font-size:14px; color:#333333;"><strong>Email:</strong> <br> {{ $order->user->email }}</p> <br>
                    <p style="margin:5px 0; font-size:14px; color:#333333;"><strong>Billing address:</strong> <br> {{ $order->billing_address->street_address }}, {{ $order->billing_address->city }}, {{ $order->billing_address->state }} {{ $order->billing_address->zip }}</p>
                  </td>
                  <td style="padding:10px;">
                    <p style="margin:0; font-size:14px; color:#333333;"><strong>Phone:</strong> <br> 0706 783 4186</p> <br>
                    <p style="margin:5px 0; font-size:14px; color:#333333;"><strong>Shipping address:</strong> <br> {{ $order->shipping_address->street_address }}, {{ $order->shipping_address->city }}, {{ $order->shipping_address->state }} {{ $order->shipping_address->zip }}</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:30px;border: 1px solid rgba(45, 52, 31, 0.2);margin-top: 15px;">
              <h4 style="margin:0 0 10px 0; font-size:16px; color:#333333;">DELIVERY</h4>
              <p style="margin:5px 0; font-size:14px; color:#666666;">Orders before 11am will be delivered 4-9pm the same day. Orders after 11am will be delivered 4-9pm the next day. We do not deliver on Sundays.</p> <br>
              <p style="margin:5px 0; font-size:14px; color:#666666;">Delivery within Kebbi: 4 - 3hrs days<br>Outside Kebbi: 4 - 7 working days.</p> <br>
              <p style="margin:5px 0; font-size:14px; color:#666666;">Allow extra day(s) for custom or bulk orders.</p>
              <p style="margin:10px 0; font-size:14px; color:#666666;">Thanks for shopping with Hi B Greenbox<br><span style="color:#759a31;">Always keep yourself nourished!</span><br><br>We look forward to fulfilling your order soon.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:20px; text-align:center;">
              <a href="#" style="margin:0 5px;text-decoration: unset;">
                <img src="{{ asset('public/Facebook.svg') }}" alt="Facebook" />
              </a>
              <a href="#" style="margin:0 5px;text-decoration: unset;">
                <img src="{{ asset('public/Insta.svg') }}" alt="Instagram" />
              </a>
              <a href="#" style="margin:0 5px;text-decoration: unset;">
                <img src="{{ asset('public/new-x.svg') }}" alt="X" />
              </a>
              <p style="margin:10px 0 0 0; font-size:12px; color:#888888;">Hi B Greenbox © 2025</p>
              <p style="margin:5px 0 0 0; font-size:12px; color:#888888;">Questions? support@hibgreenbox.com or WhatsApp (+234) 816-601-3343.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>
