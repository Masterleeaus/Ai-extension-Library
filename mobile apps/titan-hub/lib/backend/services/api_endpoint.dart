import 'package:qrpay/extentions/custom_extentions.dart';

class ApiEndpoint {
  static const String mainDomain = "PUT_YOUR_OWN_DOMAIN";

  static const String baseUrl = "$mainDomain/api";

  //! auth
  static String loginURL = '/user/login'.addBaseURl();
  static String logOutURL = '/user/logout'.addBaseURl();
  static String sendOTPEmailURL = '/user/send-code'.addBaseURl();
  static String sendForgotOTPEmailURL = '/user/forget/password'.addBaseURl();
  static String verifyForgotOTPEmailURL =
      '/user/forget/verify/otp'.addBaseURl();
  static String verifyEmailURL = '/user/email-verify'.addBaseURl();
  static String checkingUserURL =
      '/user/forget/password/check/user'.addBaseURl();
  static String resetPasswordURL = '/user/forget/reset/password'.addBaseURl();

  //!register
  static String checkRegisterURL = '/user/register/check/exist'.addBaseURl();
  static String basicDataURL = '/get/basic/data'.addBaseURl();
  static String userKycURL = '/user/kyc'.addBaseURl();
  static String registerURL = '/user/register'.addBaseURl();
  static String sendRegisterEmailOTPURL =
      '/user/register/send/otp'.addBaseURl();
  static String verifyRegisterEmailOTPURL =
      '/user/register/verify/otp'.addBaseURl();

  //! navbar
  static String dashboardURL = '/user/dashboard'.addBaseURl();
  static String notificationsURL = '/user/notifications'.addBaseURl();

  //! profile
  static String profileURL = '/user/profile'.addBaseURl();
  static String updateProfileApi = '/user/profile/update'.addBaseURl();
  static String updateKYCApi = '/user/kyc/submit'.addBaseURl();

  //! categories
  static String addMoneyInfoURL = '/user/add-money/information'.addBaseURl();

  //! drawer
  static String passwordUpdate = '/user/password/update'.addBaseURl();
  static String logout = '/user/logout'.addBaseURl();

  //! send money
  static String sendMoneyInsertURL = '/user/add-money/submit-data'.addBaseURl();
  static String sendMoneyStripeConfirmURL =
      '/user/add-money/stripe/payment/confirm'.addBaseURl();
  static String sendMoneyManualConfirmURL =
      '/user/add-money/manual/payment/confirmed'.addBaseURl();

  //flutterwave virtual card
  static String cardInfoURL = '/user/my-card'.addBaseURl();
  static String cardDetailsURL = '/user/my-card/details?card_id='.addDBaseURl();
  static String cardBlockURL = '/user/my-card/block'.addBaseURl();
  static String cardUnBlockURL = '/user/my-card/unblock'.addBaseURl();
  static String cardAddFundURL = '/user/my-card/fund'.addBaseURl();
  static String createCardURL = '/user/my-card/create'.addBaseURl();
  static String cardTransactionURL =
      '/user/my-card/transaction?card_id='.addDBaseURl();
  static String flutterWaveCardMakeOrRemoveDefaultFundURL =
      '/user/my-card/make-remove/default'.addBaseURl();

  //sudo virtual card
  static String sudoCardInfoURL = '/user/my-card/sudo'.addBaseURl();
  static String sudoCardDetailsURL =
      '/user/my-card/sudo/details?card_id='.addDBaseURl();
  static String sudoCardBlockURL = '/user/my-card/sudo/block'.addDBaseURl();
  static String sudoCardUnBlockURL = '/user/my-card/sudo/unblock'.addDBaseURl();
  static String sudoCardMakeOrRemoveDefaultFundURL =
      '/user/my-card/sudo/make-remove/default'.addBaseURl();
  static String sudoCreateCardURL = '/user/my-card/sudo/create'.addBaseURl();
  static String sudoAddFundURL = '/user/my-card/sudo/fund'.addBaseURl();
  static String sudoCardTransactionURL =
      '/user/my-card/transaction?card_id='.addDBaseURl();
  static String receiveMoneyURL = '/user/receive-money'.addBaseURl();
  static String sendMoneyInfoURL = '/user/send-money/info'.addBaseURl();
  static String checkUserExistURL = '/user/send-money/exist'.addBaseURl();
  static String checkUserWithQeCodeURL =
      '/user/send-money/qr/scan'.addBaseURl();
  static String sendMoneyURL = '/user/send-money/confirmed'.addBaseURl();

  // money_out
  static String withdrawInfoURL = '/user/withdraw/info'.addBaseURl();
  static String flutterWaveBanksBranchURL =
      '/user/withdraw/get/flutterwave/bank/branches?'.addDBaseURl();
  static String checkBankAccountURL =
      '/user/withdraw/check/flutterwave/bank/account?'.addDBaseURl();
  static String flutterWaveBanksURL =
      '/user/withdraw/get/flutterwave/banks?trx='.addDBaseURl();
  static String withdrawInsertURL = '/user/withdraw/insert'.addBaseURl();
  static String manualWithdrawConfirmURL =
      '/user/withdraw/manual/confirmed'.addBaseURl();
  static String checkFlutterwaveAccountURL =
      '/user/withdraw/check/flutterwave/bank'.addBaseURl();
  static String automaticWithdrawConfirmURL =
      '/user/withdraw/automatic/confirmed'.addBaseURl();

  static String billPayInfoURL = '/user/bill-pay/info'.addBaseURl();
  static String billPayConfirmedURL = '/user/bill-pay/confirmed'.addBaseURl();

  static String topupInfoURL = '/user/mobile-topup/info'.addBaseURl();
  static String topupConfirmedURL = '/user/mobile-topup/confirmed'.addBaseURl();
  static String topUpAutomaticConfirmedURL =
      '/user/mobile-topup/automatic/pay'.addBaseURl();
  static String automaticPayURL =
      '/user/mobile-topup/automatic/pay'.addBaseURl();
  static String checkOperatorURL =
      '/user/mobile-topup/automatic/check-operator?'.addDBaseURl();
  // recipient
  static String allRecipientURL = '/user/recipient/list'.addBaseURl();
  static String checkRecipientURL = '/user/recipient/check/user'.addBaseURl();

  static String recipientSaveInfoURL = '/user/recipient/save/info'.addBaseURl();
  static String recipientDynamicFieldURL =
      '/user/recipient/dynamic/fields'.addBaseURl();

  static String recipientCheckUserURL =
      '/user/recipient/check/user'.addBaseURl();
  static String recipientStoreURL = '/user/recipient/store'.addBaseURl();
  static String recipientUpdateURL = '/user/recipient/update'.addBaseURl();
  static String recipientDeleteURL = '/user/recipient/delete'.addBaseURl();
  static String recipientEditURL = '/user/recipient/edit?id='.addDBaseURl();

  // remittance
  static String remittanceInfoURL = '/user/remittance/info'.addBaseURl();
  static String remittanceGetRecipientURL =
      '/user/remittance/get/recipient'.addBaseURl();
  static String remittanceConfirmedURL =
      '/user/remittance/confirmed'.addBaseURl();

  // transactions
  static String transactionLogURL = '/user/transactions'.addBaseURl();

  //app settings
  static String appSettingsURL = '/app-settings'.addBaseURl();

  // 2 fa security
  static String twoFAInfoURL = '/user/security/google-2fa'.addBaseURl();
  static String twoFAVerifyURL = '/user/google-2fa/otp/verify'.addBaseURl();
  static String twoFASubmitURL =
      '/user/security/google-2fa/status/update'.addBaseURl();
  static String makePaymentInfoURL = '/user/make-payment/info'.addBaseURl();
  static String checkMerchantExistURL =
      '/user/make-payment/check/merchant'.addBaseURl();
  static String checkMerchantWithQeCodeURL =
      '/user/make-payment/merchants/scan'.addBaseURl();
  static String makePaymentURL = '/user/make-payment/confirmed'.addBaseURl();
  static String smsVerifyURL = '/user/sms/verify'.addBaseURl();
  static String deleteAccountURL = '/user/delete/account'.addBaseURl();

  // language
  static String languagesURL = '/app-settings/languages'.addBaseURl();

  //  =>>>>>>>>>> stripe virtual card
  static String stripeCardInfoURL = '/user/my-card/stripe'.addBaseURl();
  static String stripeCardDetailsURL =
      '/user/my-card/stripe/details?card_id='.addDBaseURl();
  static String stripeCardTransactionURL =
      '/user/my-card/stripe/transaction?card_id='.addDBaseURl();
  static String stripeSensitiveURl =
      '/user/my-card/stripe/get/sensitive/data'.addBaseURl();
  static String stripeInactiveURl =
      '/user/my-card/stripe/inactive'.addBaseURl();
  static String stripeActiveURl = '/user/my-card/stripe/active'.addBaseURl();
  static String stripeBuyCardURl = '/user/my-card/stripe/create'.addBaseURl();

  //-> payments
  static String paymentLinkGetURL = '/user/payment-links'.addBaseURl();
  static String paymentLinkEditGetURL =
      '/user/payment-links/edit?target='.addDBaseURl();
  static String paymentLinkStoreURL = '/user/payment-links/store'.addBaseURl();
  static String paymentLinkUpdateURL =
      '/user/payment-links/update'.addBaseURl();
  static String statusURL = '/user/payment-links/status'.addBaseURl();

  //-> Request Money
  static String requestMoneyInfoURL = '/user/request-money'.addBaseURl();
  static String requestMoneySubmitURL =
      '/user/request-money/submit'.addBaseURl();
  static String requestMoneyQrScanURL =
      '/user/request-money/qr/scan'.addBaseURl();
  static String requestMoneyCheckUserURL =
      '/user/request-money/check/user'.addBaseURl();
  static String requestMoneyLogsRejectURL =
      '/user/request-money/logs/reject'.addBaseURl();
  static String requestMoneyLogsApproveURL =
      '/user/request-money/logs/approve'.addBaseURl();
  static String requestMoneyLogsURL = '/user/request-money/logs'.addBaseURl();
  static String addMoneySubmitData = '/user/add-money/submit-data'.addBaseURl();

  // Strowallet
  static String strowalletCardURL = '/user/strowallet-card'.addBaseURl();
  static String strowalletCardChargeURL =
      '/user/strowallet-card/charges'.addBaseURl();
  static String strowalletCardDetailsURL =
      '/user/strowallet-card/details?card_id='.addDBaseURl();
  static String strowalletCardTransactionURL =
      '/user/strowallet-card/transaction?card_id='.addDBaseURl();
  static String strowalletCardBLockURL =
      '/user/strowallet-card/block'.addBaseURl();
  static String strowalletCardUnBlockURL =
      '/user/strowallet-card/unblock'.addBaseURl();
  static String strowalletBuyCardURL =
      '/user/strowallet-card/create'.addBaseURl();
  static String strowalletCardFundURL =
      '/user/strowallet-card/fund'.addBaseURl();

  static String strowalletCardMakeOrRemoveDefaultFundURL =
      '/user/strowallet-card/make-remove/default'.addBaseURl();

  static String strowalletCardInfo =
      '/user/strowallet-card/create/info'.addBaseURl();
  static String strowalletCreateCustomerURL =
      '/user/strowallet-card/create/customer'.addBaseURl();
  static String strowalletCreateCustomerStatusURl =
      '/user/strowallet-card/update/customer/status'.addBaseURl();
  static String strowalletUpdateCustomerURl =
      '/user/strowallet-card/update/customer'.addBaseURl();

  //->>>>  Agent Money Out
  static String agentMoneyOutInfoURL = '/user/money-out/info'.addBaseURl();
  static String agentMoneyCheckURL = '/user/money-out/check/agent'.addBaseURl();
  static String qrCodeScanAgentMoneyOutURL =
      '/user/money-out/qr/scan'.addBaseURl();
  static String agentMoneyOutConfirm = '/user/money-out/confirmed'.addBaseURl();

  //->>>>  Pusher
  static String pusherBeamsAuthURL =
      '/user/pusher/beams-auth?user_id='.addDBaseURl();
  static String pusherBeamsAuthMain = '/user/pusher/beams-auth'.addDBaseURl();

  // Gift Card
  static String myGiftCardURL = '/user/gift-card'.addBaseURl();
  static String allGiftCardURL = '/user/gift-card/all'.addDBaseURl();
  static String searchGiftCardURL =
      '/user/gift-card/search?country='.addDBaseURl();
  static String giftCardDetailsURL =
      '/user/gift-card/details?product_id='.addDBaseURl();
  static String giftCardOrderURL = '/user/gift-card/order'.addBaseURl();
  // remaining balance
  static String remainingBalanceURL =
      '/user/get-remaining?transaction_type='.addDBaseURl();
  static String webhookLogsURL =
      '/user/strowallet-card/webhook?card_id='.addDBaseURl();
}
