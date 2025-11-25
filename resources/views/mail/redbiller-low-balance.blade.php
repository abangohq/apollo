<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Koyn</title>
    <style type="text/css">
        a {
            text-decoration: underline;
            color: #9042F8;
            font-family: 'segoe ui', segoe, 'avenir next', 'open sans', 'noto sans', sans-serif;
        }

        .bg-primary {
            background: #9042F8;
        }

        .border-color {
            border-bottom: 1px solid #F2F2F2 !important;
        }

        .color-primary {
            color: #9042F8 !important;
        }

        .container {
            background: #fff;
            color: #525054;
            padding: 3.6rem 2rem 3.6rem 2rem;
        }

        .body-bg {
            background: #fff !important;
            background-color: #fff !important;
        }

        h3 {
            font-weight: 600;
            font-size: 1.75rem;
            line-height: normal;
            color: #0A0510;
            font-family: 'segoe ui', segoe, 'avenir next', 'open sans', 'noto sans', sans-serif;
        }


        @media (prefers-color-scheme: dark) {
            .bg-primary {
                background: #9042F8;
            }

            .border-color {
                border-bottom: 1px solid #1A1A1A !important;
            }

            .color-primary {
                color: #9042F8 !important;
            }

            .container {
                background: #0A0510;
                color: #525054;
                padding: 3.6rem 2rem 3.6rem 2rem;
            }

            .body-bg {
                background: #0A0510 !important;
                background-color: #0A0510 !important;
            }

            h3 {
                color: #fff;

            }
        }

        @media (prefers-color-scheme: light) {
            .bg-primary {
                background: #9042F8;
            }

            .border-color {
                border-bottom: 1px solid #F2F2F2 !important;
            }

            .color-primary {
                color: #9042F8 !important;
            }

            .container {
                background: #fff;
                color: #525054;
                padding: 3.6rem 1.5rem 3.6rem 1.5rem;
            }

            .body-bg {
                background: #fff !important;
                background-color: #fff !important;
            }
        }

        p {
            font-size: 1rem;
            line-height: normal;
            color: #525054;
            font-family: 'segoe ui', segoe, 'avenir next', 'open sans', 'noto sans', sans-serif;
        }
    </style>
</head>

<body style="margin: 0 auto;
padding: 0;
box-sizing: border-box;
-webkit-text-size-adjust: 100%;
font-size: 62.5%;
max-width: 50rem;
height: 100%;
font-family: 'segoe ui', segoe, 'avenir next', 'open sans', 'noto sans', sans-serif;" class="body-bg">
    <div class="container">
        <div style="
        padding-bottom: 1rem;
       ">
            <img id="logo" style="height: 3.125rem;" alt="koyn-icon"
                src="https://res.cloudinary.com/diyxzs220/image/upload/v1698745384/Frame_427319130_myu5fl.png" />
        </div>
        <div class="content">
            <div>
                </p>
              <p style="margin-top: 1rem;">
              The current Redbiller balance is ₦{{ $balance }}, which is below the threshold of ₦500,000.
              </p>
                <p style="margin-top: 1rem;">
                Please top up the balance as soon as possible.
                </p>
            </div>
        </div>
        <div id="footer" style="padding: 1.5rem 0;">
            <p style="font-size: 0.75rem;">Koyn is the easiest way to sell your cryptocurrency, pay bills, top up bet
                accounts and more. <a style="color:#525054;" href="#">Learn More</a></p>
            <p style="font-size: 0.75rem; padding-top: .1rem">You are receiving this email because you signed up on
                Koyn. <a style="color:#525054;" href="#">Unsubscribe</a></p>
            <div style="padding-top: 1rem;">
                <a style="margin-right: 0.5rem" href="#"><img style="width: 1.2rem;"
                        src="https://res.cloudinary.com/diyxzs220/image/upload/v1689583293/email/facebook_jthxv5.png"
                        alt="facebook" /></a>
                <a href="#"><img style="width: 1.2rem;"
                        src="https://res.cloudinary.com/diyxzs220/image/upload/v1689583294/email/instagram_ck31si.png"
                        alt="instagram" /></a>
            </div>
        </div>
    </div>
</body>

</html>