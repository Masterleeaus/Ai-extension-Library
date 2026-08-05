import 'package:qrpay/routes/route_pages.dart';

class Routes {
  // Page List
  static var list = RoutePageList.list;

  // Route Names
  //!auth
  static const String splashScreen = '/splashScreen';
  static const String unsupportedCountryScreen = '/splashScreen2';
  static const String exampleScreen = '/exampleScreen';
  static const String onboardScreen = '/onboardScreen';

  // static const String biometricScreen = '/biometricScreen';
  static const String signInScreen = '/signInScreen';
  static const String resetOtpScreen = '/resetOtpScreen';
  static const String resetPasswordScreen = '/resetPasswordScreen';
  static const String emailOtpScreen = '/emailOtpScreen';
  static const String registrationScreen = '/registrationScreen';

  static const String fingerprintScreen = '/fingerprintScreen';
  static const String kycFromScreen = '/kycFromScreen';
  static const String waitForApprovalScreen = '/waitForApprovalScreen';

  //!dashboard
  static const String bottomNavBarScreen = '/bottomNavBarScreen';
  static const String dashboardScreen = '/dashboardScreen';
  static const String qRCodeScreen = '/qRCodeScreen';
  static const String notificationScreen = '/notificationScreen';

  //!categories
  static const String moneyTransferScreen = '/moneyTransferScreen';
  static const String sendMoneyPreviewScreen = '/sendMoneyPreviewScreen';
  static const String moneyReceiveScreen = '/moneyReceiveScreen';
  static const String remittanceScreen = '/remittanceScreen';
  static const String remittancePreviewScreen = '/remittancePreviewScreen';
  static const String addRecipientScreen = '/addRecipientScreen';
  static const String editRecipientScreen = '/editRecipientScreen';

  static const String makePaymentScreen = '/makePaymentScreen';
  static const String makePaymentPreviewScreen = '/makePaymentPreviewScreen';
  static const String makePaymentQRCodeScreen = '/makePaymentQRCodeScreen';

  // virtual card
  static const String virtualCardScreen = '/virtualCardScreen';
  static const String cardDetailsScreen = '/cardDetailsScreen';
  static const String addFundScreen = '/addFundScreen';
  static const String buyCardScreen = '/buyCard';
  static const String addFundPreviewScreen = '/addFundPreviewScreen';
  static const String transactionHistoryScreen = '/transactionHistoryScreen';

  //deposit profile
  static const String billPayScreen = '/billPayScreen';
  static const String billPayPreviewScreen = '/billPayPreviewScreen';
  static const String addMoneyScreen = '/depositScreen';
  static const String depositPreviewScreen = '/depositPreviewScreen';
  static const String mobileToUpScreen = '/mobileTopUpScreen';
  static const String mobilePreviewScreen = '/mobilePreviewScreen';
  static const String withdrawScreen = '/withdrawScreen';
  static const String withdrawPreviewScreen = '/withdrawPreviewScreen';
  static const String pagaditoWebPaymentScreen = '/pagaditoWebPaymentScreen';
  static const String bkashWebPaymentScreen =
      '/bkashWebPaymentScreen';
  //! drawer screens
  static const String settingScreen = '/settingScreen';
  static const String helpCenterScreen = '/helpCenterScreen';
  static const String aboutUsScreen = '/aboutUsScreen';
  static const String privacyScreen = '/privacyScreen';
  static const String changePasswordScreen = '/changePasswordScreen';
  static const String mobileLogScreen = '/mobileLogScreen';
  static const String billPaymentLogScreen = '/billPaymentLogScreen';
  static const String giftCardLogScreen = '/giftCardLogScreen';
  static const String transactionLogScreen = '/transactionLogScreen';
  static const String saveRecipientScreen = '/saveRecipientScreen';

  //!profile
  static const String myWalletScreen = '/myWalletScreen';
  static const String profileScreen = '/profileScreen';

  static const String buyGiftCardDetailsScreen = '/buyGiftCardDetailsScreen';
  static const String enable2FaScreen = '/enable2FaScreen';
  static const String otp2FaScreen = '/otp2FaScreen';
  static const String buyCardPreviewScreen = '/buyCardPreviewScreen';
  static const String updateProfileScreen = '/updateProfileScreen';
  static const String updateKycScreen = '/updateKycScreen';
  static const String paypalWebPaymentScreen = '/webPaymentScreen';
  static const String flutterWaveWebPaymentScreen = '/webPaymentScreen2';
  static const String sendMoneyStripeAnimatedScreen =
      '/sendMoneyStripeAnimatedScreen';
  static const String razorPayScreen = '/razorPayScreen';
  static const String sendMoneyManualPaymentScreen =
      '/sendMoneyManualPaymentScreen';
  static const String withdrawManualPaymentScreen =
      '/moneyOutManualPaymentScreen';
  static const String withdrawFlutterwaveScreen = '/withdrawFlutterwaveScreen';

  static const String emailVerificationScreen = '/smsVerificationScreen';
  static const String sudoCardDetailsScreen = '/sudoCardDetailsScreen';
  static const String sudoAddFundScreen = '/sudoAddFundScreen';
  static const String sudoCreateCardScreen = '/sudoCreateCardScreen';
  static const String sudoAddFuncCardScreen = '/sudoAddFuncCardScreen';
  static const String stripeWebPaymentScreen = '/stripeWebPaymentScreen';
  static const String sslWebPaymentScreen = '/sslWebPaymentScreen';
  static const String coinGatePaymentScreen = '/coinGatePaymentScreen';
  static const String paymentLogScreen = '/paymentLogScreen';
  static const String paymentsEditScreen = '/paymentsEditScreen';
  static const String paymentsScreen = '/paymentsScreen';
  static const String requestMoneyScreen = '/requestMoneyScreen';
  static const String requestMoneyPreviewScreen = '/requestMoneyPreviewScreen';
  static const String requestMoneyQRCodeScreen = '/requestMoneyQRCodeScreen';
  static const String requestMoneyLogScreen = '/requestMoneyLogScreen';
  static const String tatumPaymentScreen = '/tatumPaymentScreen';
  static const String perfectMoneyWebPaymentScreen =
      '/perfectMoneyWebPaymentScreen';
  //>>>> agent
  static const String agentMoneyOutScreen = '/agentMoneyOutScreen';
  static const String agentMoneyScreenPreviewScreen =
      '/agentMoneyScreenPreviewScreen';
  static const String qRCodeAgentMoneyOutScreen = '/qRCodeAgentMoneyOutScreen';

  // Strowallet screen
  static const String strowalletCardDetailsScreen =
      '/strowalletCardDetailsScreen';
  static const String strowalletTransactionHistoryScreen =
      '/strowalletTransactionHistoryScreen';
  static const String strowalletAddFundScreen = '/strowalletAddFundScreen';
  static const String crateStrowalletScreen = '/crateStrowalletScreen';
    static const String webhookLogsScreen = '/webhookLogsScreen'; 

  //>>>.. gift card
  static const String giftCardScreen = '/giftCardScreen';
  static const String allGiftCardScreen = '/allGiftCardScreen';
  static const String createGiftCardScreen = '/createGiftCardScreen';
  static const String updateCustomerKycScreen = '/updateCustomerKycScreen';
}
