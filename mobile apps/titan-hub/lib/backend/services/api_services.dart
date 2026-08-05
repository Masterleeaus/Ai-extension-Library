import 'package:get_storage/get_storage.dart';
import 'package:http/http.dart' as http;
import 'package:qrpay/backend/model/add_money/add_money_ssl_insert_model.dart';
import 'package:qrpay/backend/model/auth/login/reset_password.dart';
import 'package:qrpay/backend/model/auth/registation/basic_data_model.dart';
import 'package:qrpay/backend/model/categories/receive_money/receive_money_model.dart';
import 'package:qrpay/backend/model/categories/send_money/send_money_info_model.dart';
import 'package:qrpay/backend/model/categories/virtual_card/virtual_card_flutter_wave/card_info_model.dart';
import 'package:qrpay/backend/model/recipient_models/check_recipient_model.dart';

import '../model/add_money/add_model_bkash_model.dart';
import '../model/add_money/add_money_pagadito_insert_model.dart';
import '../model/add_money/add_money_payment_gateway_model.dart';
import '../model/add_money/add_money_paypal_insert_model.dart';
import '../model/add_money/add_money_razorpay_insert_model.dart';
import '../model/add_money/add_money_stripe_insert_model.dart';
import '../model/add_money/coingate_insert_model.dart';
import '../model/add_money/send_money_manual_insert_model.dart';
import '../model/add_money/tatum_gateway_model.dart';
import '../model/app_settings/app_settings_model.dart';
import '../model/auth/login/forget_password_model.dart';
import '../model/auth/login/login_model.dart';
import '../model/auth/registation/check_register_user_model.dart';
import '../model/auth/registation/registration_with_kyc_model.dart';
import '../model/bottom_navbar_model/dashboard_model.dart';
import '../model/bottom_navbar_model/notification_model.dart';
import '../model/categories/agent-moneyout/agent_money_info_model.dart';
import '../model/categories/agent-moneyout/check_user_with_qr_code.dart';
import '../model/categories/bill_pay_model/bill_pay_model.dart';
import '../model/categories/deposit/add_money_flutter_wave_model.dart';
import '../model/categories/make_payment/check_merchant_scan_model.dart';
import '../model/categories/make_payment/make_payment_info_model.dart';
import '../model/categories/send_money/check_user_with_qr_code.dart';
import '../model/categories/topup/detect_operator_model.dart';
import '../model/categories/topup/topup_info_model.dart';
import '../model/categories/virtual_card/stripe_models/stripe_card_details_model.dart';
import '../model/categories/virtual_card/stripe_models/stripe_card_info_model.dart';
import '../model/categories/virtual_card/stripe_models/stripe_card_sensitive_model.dart';
import '../model/categories/virtual_card/stripe_models/stripe_transaction_model.dart';
import '../model/categories/virtual_card/virtual_card_flutter_wave/card_details_model.dart';
import '../model/categories/virtual_card/virtual_card_flutter_wave/card_transaction_model.dart';
import '../model/categories/virtual_card/virtual_card_sudo/virtual_card_sudo_info_model.dart';
import '../model/categories/virtual_card/virtual_card_sudo/sudo_card_details_model.dart';
import '../model/categories/withdraw/flutter_wave_banks_model.dart';
import '../model/categories/withdraw/flutterwave_account_cheack_model.dart';
import '../model/categories/withdraw/money_out_manual_insert_model.dart';
import '../model/categories/withdraw/money_out_payment_getway_model.dart';
import '../model/categories/withdraw/withdraw_flutterwave_insert_model.dart';
import '../model/common/common_success_model.dart';
import '../model/profile/profile_model.dart';
import '../model/recipient_models/all_recipient_model.dart';
import '../model/recipient_models/recipient_dynamic_field_model.dart';
import '../model/recipient_models/recipient_edit_model.dart';
import '../model/recipient_models/recipient_save_info_model.dart';
import '../model/remaining_blance_model/remaining_balance_model.dart';
import '../model/remittance/remittance_get_recipient_model.dart';
import '../model/remittance/remittance_info_model.dart';
import '../model/transaction_log/transaction_log_model.dart';
import '../model/two_fa/two_fa_info_model.dart';
import '../model/update_kyc/update_kyc_model.dart';
import '../utils/api_method.dart';
import '../utils/custom_snackbar.dart';
import '../utils/logger.dart';
import 'api_endpoint.dart';

final log = logger(ApiServices);

class ApiServices {
  static var client = http.Client();
  // Generic API service method
  static Future<T?> apiService<T>(
    T Function(Map<String, dynamic>) fromJson,
    String apiEndpoint, {
    Map<String, dynamic>? body,
    String method = 'GET',
    int statusCode = 200,
    bool showResult = false,
    bool isBasic = false,
    bool showSuccessMessage = false,
    String tostTitle = 'Success',
  }) async {
    try {
      Map<String, dynamic>? mapResponse;

      if (method == 'POST') {
        mapResponse = await ApiMethod(isBasic: isBasic).post(
          apiEndpoint,
          body ?? {},
          code: statusCode,
        );
      } else if (method == 'GET') {
        mapResponse = await ApiMethod(isBasic: isBasic).get(
          apiEndpoint,
          showResult: showResult,
        );
      }

      if (mapResponse != null) {
        return _handleResponse<T>(
            mapResponse, fromJson, showSuccessMessage, tostTitle);
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from ApiService ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went wrong!');
    }
    return null;
  }

  // Method for multipart file uploads
  static Future<T?> multipartApiService<T>(
    T Function(Map<String, dynamic>) fromJson,
    String apiEndpoint,
    Map<String, String> body,
    List<String> fieldList,
    List<String> pathList, {
    int statusCode = 200,
    bool isBasic = false,
    bool showSuccessMessage = false,
    String tostTitle = 'Success',
  }) async {
    try {
      Map<String, dynamic>? mapResponse =
          await ApiMethod(isBasic: isBasic).multipartMultiFile(
        apiEndpoint,
        body,
        fieldList: fieldList,
        pathList: pathList,
      );

      if (mapResponse != null) {
        return _handleResponse<T>(
            mapResponse, fromJson, showSuccessMessage, tostTitle);
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from multipartApiService ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went wrong!');
    }
    return null;
  }

  // Helper method to handle responses
  static T? _handleResponse<T>(
    Map<String, dynamic> mapResponse,
    T Function(Map<String, dynamic>) fromJson,
    bool showSuccessMessage,
    String tostTitle,
  ) {
    T result = fromJson(mapResponse);

    if (showSuccessMessage) {
      var messages = CommonSuccessModel.fromJson(mapResponse);
      CustomSnackBar.success(
        messages.message.success.first.toString(),
      );
    }

    return result;
  }

// App Settings Api
  static Future<AppSettingsModel?> appSettingsApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).get(
        ApiEndpoint.appSettingsURL,
        showResult: true,
      );
      if (mapResponse != null) {
        AppSettingsModel appSettingsModel =
            AppSettingsModel.fromJson(mapResponse);

        return appSettingsModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from App Settings Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in App Settings Model');
      return null;
    }
    return null;
  }

//!Login Api method
  static Future<LoginModel?> loginApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.loginURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        LoginModel loginModel = LoginModel.fromJson(mapResponse);
        // CustomSnackBar.success(loginModel.message.success.first.toString());
        return loginModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from login api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in LoginModel');
      return null;
    }
    return null;
  }

  //!Login Api method
  static Future<CommonSuccessModel?> twoFaSubmitApiProcess(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.twoFASubmitURL,
        body,
        code: 200,
        showResult: true,
      );
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from login api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in CommonSuccessModel');
      return null;
    }
    return null;
  }

  //!Logout Api method
  static Future<CommonSuccessModel?> logOut() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.logOutURL,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel logOutModel =
            CommonSuccessModel.fromJson(mapResponse);
        return logOutModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from log out api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in logout model');
      return null;
    }
    return null;
  }

  // send otp email
  static Future<CommonSuccessModel?> sendOTPEmailApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sendOTPEmailURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel checkUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(checkUserModel.message.success.first.toString());
        return checkUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send otp email service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in check  checkUserModel');
      return null;
    }
    return null;
  }

  // verify Email User Process
  static Future<CommonSuccessModel?> verifyEmailApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.verifyEmailURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel verifyEmailUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(
            verifyEmailUserModel.message.success.first.toString());
        return verifyEmailUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send otp email service ==> $e 🐞🐞🐞');
      CustomSnackBar.error(
          'Something went Wrong! in verify Email  checkUserModel');
      return null;
    }
    return null;
  }

//! forgot password
  static Future<CheckRegisterUserModel?> checkRegisterApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.checkRegisterURL,
        body,
        code: 200,
        duration: 25,
      );
      if (mapResponse != null) {
        CheckRegisterUserModel checkRegisterModel =
            CheckRegisterUserModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     checkRegisterModel.message.success.first.toString());
        return checkRegisterModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from check register user service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in check register user');
      return null;
    }
    return null;
  }

  //! reset password api service
  static Future<ResetPasswordModel?> resetPasswordApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.resetPasswordURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        ResetPasswordModel resetPasswordModel =
            ResetPasswordModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     resetPasswordModel.message.success.first.toString());
        return resetPasswordModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from reset Password api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in Reset');
      return null;
    }
    return null;
  }

//!Register Api Service

  // send otp email
  static Future<CommonSuccessModel?> sendRegisterOTPEmailApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.sendRegisterEmailOTPURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel checkUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(checkUserModel.message.success.first.toString());
        return checkUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send register otp email service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in check  checkUserModel');
      return null;
    }
    return null;
  }

// check register exist
  static Future<CheckUserModel?> checkUserApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.checkingUserURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CheckUserModel checkUserModel = CheckUserModel.fromJson(mapResponse);
        // CustomSnackBar.success(checkUserModel.message.success.first.toString());
        return checkUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from check user  service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in check  user model');
      return null;
    }
    return null;
  }

  // verify register Email User Process
  static Future<CommonSuccessModel?> verifyRegisterEmailApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.verifyRegisterEmailOTPURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel verifyEmailUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(
            verifyEmailUserModel.message.success.first.toString());
        return verifyEmailUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send otp email service ==> $e 🐞🐞🐞');
      CustomSnackBar.error(
          'Something went Wrong! in verify Email  checkUserModel');
      return null;
    }
    return null;
  }

  // basic data get
  static Future<BasicDataModel?> basicData() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).get(
        ApiEndpoint.basicDataURL,
        code: 200,
      );
      if (mapResponse != null) {
        BasicDataModel basicDataModel = BasicDataModel.fromJson(mapResponse);

        return basicDataModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from basic data Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in basic data Api');
      return null;
    }
    return null;
  }

  // registration with kyc
  static Future<RegistrationWithKycModel?> registrationApi({
    required Map<String, String> body,
    required List<String> pathList,
    required List<String> fieldList,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).multipartMultiFile(
        ApiEndpoint.registerURL,
        body,
        showResult: true,
        fieldList: fieldList,
        pathList: pathList,
      );
      if (mapResponse != null) {
        RegistrationWithKycModel registrationModel =
            RegistrationWithKycModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     registrationModel.message.success.first.toString());
        return registrationModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from registration api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in registration Model');
      return null;
    }
    return null;
  }

//!  dashboard Api method

  static Future<DashboardModel?> dashboardApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .get(ApiEndpoint.dashboardURL, code: 200, isNotStream: false);
      if (mapResponse != null) {
        DashboardModel dashboardModel = DashboardModel.fromJson(mapResponse);

        return dashboardModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from Dashboard Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in Dashboard Api');
      return null;
    }
    return null;
  }

  /// get notification process
  static Future<NotificationModel?> getNotificationAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.notificationsURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        NotificationModel modelData = NotificationModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from notification info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  //! profile section started

  static Future<ProfileModel?> profileAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.dashboardURL,
        code: 200,
      );
      if (mapResponse != null) {
        ProfileModel profileModel = ProfileModel.fromJson(mapResponse);

        return profileModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from profile Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in profile Api');
      return null;
    }
    return null;
  }

  //! update profile APi

//  update profile  Api method
  static Future<CommonSuccessModel?> updateProfileWithoutImageApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.updateProfileApi,
        body,
        code: 200,
        showResult: true,
      );
      if (mapResponse != null) {
        CommonSuccessModel updateProfileModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     updateProfileModel.message.success.first.toString());
        return updateProfileModel;
      }
    } catch (e) {
      log.e('err from update profile api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // with img update profile api
  static Future<CommonSuccessModel?> updateProfileWithImageApi(
      {required Map<String, String> body, required String filepath}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).multipart(
          ApiEndpoint.updateProfileApi, body, filepath, 'image',
          code: 200);

      if (mapResponse != null) {
        CommonSuccessModel profileUpdateModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     profileUpdateModel.message.success.first.toString());
        return profileUpdateModel;
      }
    } catch (e) {
      log.e('err from profile update api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //! categories section started
  // add_money_get

  static Future<AddMoneyPaymentGatewayModel?>
      addMoneyPaymentGatewayAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.addMoneyInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        AddMoneyPaymentGatewayModel addMoneyPaymentGatewayModel =
            AddMoneyPaymentGatewayModel.fromJson(mapResponse);

        return addMoneyPaymentGatewayModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from Add Money Payment Gateway api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  //!drawer
  static Future<CommonSuccessModel?> passwordUpdateApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.passwordUpdate, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel updateProfileModel =
            CommonSuccessModel.fromJson(mapResponse);

        return updateProfileModel;
      }
    } catch (e) {
      log.e('err from update password api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// ---
//  send money insert paypal  Api method
  static Future<AddMoneyPaypalInsertModel?> sendMoneyInsertPaypalApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyPaypalInsertModel addMoneyPaypalInsertModel =
            AddMoneyPaypalInsertModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     sendMoneyPaypalInsertModel.message.success.first.toString());
        return addMoneyPaypalInsertModel;
      }
    } catch (e) {
      log.e('err from add money paypal api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  send money insert paypal  Api method
  static Future<AddMoneyFlutterWavePaymentModel?> sendMoneyInsertFlutterWaveApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyFlutterWavePaymentModel sendMoneyFlutterWaveInsertModel =
            AddMoneyFlutterWavePaymentModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     sendMoneyFlutterWaveInsertModel.message.success.first.toString());
        return sendMoneyFlutterWaveInsertModel;
      }
    } catch (e) {
      log.e('err from send money flutter wave api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  add money coinGate Api method
  static Future<CoinGateInsertModel?> addMoneyCoinGateProcessApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        CoinGateInsertModel result = CoinGateInsertModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('err from add money coinGate api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  add money insert ssl  Api method
  static Future<AddMoneySslInsertModel?> addMoneySslProcessApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneySslInsertModel result =
            AddMoneySslInsertModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('err from add money insert ssl api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // Add Money Insert RazorPay api
  static Future<AddMoneyRazorPayInsertModel?> addMoneyInsertRazorPayApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyRazorPayInsertModel result =
            AddMoneyRazorPayInsertModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('err from Add Money Insert RazorPay api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //bkash add money
  static Future<AddMoneyBkashInsertModel?> addMoneyInsertBkashApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyBkashInsertModel result =
            AddMoneyBkashInsertModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('err from Add Money Insert Bkash api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

//  send money insert paypal  Api method
  static Future<AddMoneyStripeInsertModel?> addMoneyInsertStripeApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyStripeInsertModel sendMoneyStripeInsertModel =
            AddMoneyStripeInsertModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     sendMoneyStripeInsertModel.message.success.first.toString());
        return sendMoneyStripeInsertModel;
      }
    } catch (e) {
      log.e('err from send money Stripe api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

//  send money insert stripe  Api method
  static Future<CommonSuccessModel?> addMoneyStripeConfirmApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyStripeConfirmURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel sendMoneyStripeConfirmModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     sendMoneyStripeConfirmModel.message.success.first.toString());
        return sendMoneyStripeConfirmModel;
      }
    } catch (e) {
      log.e('err from send money Stripe api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

//  send money manual insert  Api method
  static Future<AddMoneyManualInsertModel?> addMoneyManualInsertApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyManualInsertModel sendMoneyManualInsertModel =
            AddMoneyManualInsertModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     sendMoneyManualInsertModel.message.success.first.toString());
        return sendMoneyManualInsertModel;
      }
    } catch (e) {
      log.e('err from send money manual api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //manual payment confirm Api method
  static Future<CommonSuccessModel?> manualPaymentConfirmApi({
    required Map<String, String> body,
    required List<String> pathList,
    required List<String> fieldList,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).multipartMultiFile(
          ApiEndpoint.sendMoneyManualConfirmURL, body,
          fieldList: fieldList, pathList: pathList, code: 200);

      if (mapResponse != null) {
        CommonSuccessModel sendMoneyManualConfirmModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(
        //     sendMoneyManualConfirmModel.message.success.first.toString());
        return sendMoneyManualConfirmModel;
      }
    } catch (e) {
      log.e('err from manual payment Confirm api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // Add Money Insert Pagadito api
  static Future<AddMoneyPagaditoInsertModel?> addMoneyInsertPagaditoApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.sendMoneyInsertURL, body, code: 200);
      if (mapResponse != null) {
        AddMoneyPagaditoInsertModel result =
            AddMoneyPagaditoInsertModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('err from Add Money Insert Pagadito api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //
  // ---------------------------------------------------------------------------
  //                              Virtual Card
  // ---------------------------------------------------------------------------
  //

  // card info api
  static Future<CardInfoModel?> cardInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.cardInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CardInfoModel cardInfoModel = CardInfoModel.fromJson(mapResponse);

        return cardInfoModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card details api
  static Future<CardDetailsModel?> cardDetailsApi({required String id}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.cardDetailsURL + id}&lang=${GetStorage().read('selectedLanguage')}",
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CardDetailsModel cardDetailsModel =
            CardDetailsModel.fromJson(mapResponse);

        return cardDetailsModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card details api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card block api
  static Future<CommonSuccessModel?> cardBlockApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.cardBlockURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card block api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card block api
  static Future<CommonSuccessModel?> cardUnBlockApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.cardUnBlockURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card unblock api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card add fund api
  static Future<CommonSuccessModel?> carAddFundApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.cardAddFundURL,
        body,
        code: 200,
        duration: 45,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardAddFundModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardAddFundModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card add fund  api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card create api
  static Future<CommonSuccessModel?> createCardApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.createCardURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardAddFundModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardAddFundModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card add fund  api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card transaction api
  static Future<CardTransactionModel?> cardTransactionApi(
      {required String id}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.cardTransactionURL + id}&lang=${GetStorage().read('selectedLanguage')}",
        code: 200,
        duration: 45,
        showResult: false,
      );
      if (mapResponse != null) {
        CardTransactionModel cardTransactionModel =
            CardTransactionModel.fromJson(mapResponse);

        return cardTransactionModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card transaction api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

//
// ---------------------------------------------------------------------------
//                               Receive Money
// ---------------------------------------------------------------------------
//

  // Receive Money api
  static Future<ReceiveMoneyModel?> receiveMoneyApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.receiveMoneyURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        ReceiveMoneyModel receiveMoneyModel =
            ReceiveMoneyModel.fromJson(mapResponse);

        return receiveMoneyModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from receive money api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  //
  // ---------------------------------------------------------------------------
  //                              Send Money
  // ---------------------------------------------------------------------------
  //

  // card info api
  static Future<SendMoneyInfoModel?> sendMoneyInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.sendMoneyInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        SendMoneyInfoModel sendMoneyInfoModel =
            SendMoneyInfoModel.fromJson(mapResponse);

        return sendMoneyInfoModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send money info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CommonSuccessModel?> checkUserExistApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.checkUserExistURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from check user exist api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CheckUserWithQrCodeModel?> checkUserWithQrCodeApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.checkUserWithQeCodeURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CheckUserWithQrCodeModel checkUserWithQrCodeModel =
            CheckUserWithQrCodeModel.fromJson(mapResponse);

        return checkUserWithQrCodeModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from check user with qr code api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CommonSuccessModel?> sendMoneyApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sendMoneyURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel sendMoneyModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(sendMoneyModel.message.success.first);
        return sendMoneyModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send money api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  /// money out all process
  static Future<WithdrawInfoModel?> withdrawInfoAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.withdrawInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        WithdrawInfoModel addMoneyPaymentGatewayModel =
            WithdrawInfoModel.fromJson(mapResponse);

        return addMoneyPaymentGatewayModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from Money Out Payment Gateway api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in money out info Api');
      return null;
    }
    return null;
  }

  /// get flutter wave banks list
  static Future<FlutterWaveBanksModel?> getFlutterWaveBanksApi(
      String trx) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.flutterWaveBanksURL}$trx&lang=${GetStorage().read('selectedLanguage')}",
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        FlutterWaveBanksModel result =
            FlutterWaveBanksModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from get flutter wave banks list api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error(
          'Something went Wrong! in Flutter wave banks list Api');
      return null;
    }
    return null;
  }

  //  money out manual insert  Api method
  static Future<WithdrawManualInsertModel?> withdrawManualInsertApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.withdrawInsertURL, body, code: 200);
      if (mapResponse != null) {
        WithdrawManualInsertModel sendMoneyManualInsertModel =
            WithdrawManualInsertModel.fromJson(mapResponse);
        // // CustomSnackBar.success(
        //     sendMoneyManualInsertModel.message.success.first.toString());
        return sendMoneyManualInsertModel;
      }
    } catch (e) {
      log.e('err from money out manual api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  withdraw automatic flutterwave insert  Api method
  static Future<WithdrawFlutterWaveInsertModel?>
      withdrawAutomaticFluuerwaveInsertApi(
          {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.withdrawInsertURL, body, code: 200);
      if (mapResponse != null) {
        WithdrawFlutterWaveInsertModel sendMoneyManualInsertModel =
            WithdrawFlutterWaveInsertModel.fromJson(mapResponse);
        // // CustomSnackBar.success(
        //     sendMoneyManualInsertModel.message.success.first.toString());
        return sendMoneyManualInsertModel;
      }
    } catch (e) {
      log.e('err from money out manual api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  withdraw automatic flutterwave insert  Api method
  static Future<CommonSuccessModel?> withdrawFluuerwaveConfirmApiApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.automaticWithdrawConfirmURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel sendMoneyManualInsertModel =
            CommonSuccessModel.fromJson(mapResponse);
        // // CustomSnackBar.success(
        //     sendMoneyManualInsertModel.message.success.first.toString());
        return sendMoneyManualInsertModel;
      }
    } catch (e) {
      log.e('err from money out manual api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  withdraw automatic flutterwave insert  Api method
  static Future<FlutterwaveAccountCheckModel?> flutterwaveAccountCheackerApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.checkFlutterwaveAccountURL, body, code: 200);
      if (mapResponse != null) {
        FlutterwaveAccountCheckModel sendMoneyManualInsertModel =
            FlutterwaveAccountCheckModel.fromJson(mapResponse);
        // // CustomSnackBar.success(
        //     sendMoneyManualInsertModel.message.success.first.toString());
        return sendMoneyManualInsertModel;
      }
    } catch (e) {
      log.e('err from money out manual api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //manual payment confirm Api method
  static Future<CommonSuccessModel?> manualPaymentConfirmApiForWithdraw({
    required Map<String, String> body,
    required List<String> pathList,
    required List<String> fieldList,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).multipartMultiFile(
          ApiEndpoint.manualWithdrawConfirmURL, body,
          fieldList: fieldList, pathList: pathList, code: 200);

      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from manual payment money out Confirm api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// bill pay all process
  static Future<BillPayInfoModel?> billPayInfoAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.billPayInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        BillPayInfoModel modelData = BillPayInfoModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from bill pay info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in bill pay info Api');
      return null;
    }
    return null;
  }

  //  money out manual insert  Api method
  static Future<CommonSuccessModel?> billPayConfirmedApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.billPayConfirmedURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from bill pay conf api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// top up all process
  static Future<TopUpInfoModel?> topupInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.topupInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        TopUpInfoModel modelData = TopUpInfoModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from Topup Payment Gateway api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in Topup info Api');
      return null;
    }
    return null;
  }

  ///
  static Future<DetectOperatorModel?> getDetectOperator({
    required String mobileNumber,
    required String countryCode,
    required String mobileCode,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.checkOperatorURL}mobile_code=$mobileCode&mobile_number=$mobileNumber&country_code=$countryCode",
        code: 200,
        showResult: true,
      );
      if (mapResponse != null) {
        DetectOperatorModel result = DetectOperatorModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from Detect Operator Model api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // topup insert  Api method
  static Future<CommonSuccessModel?> topupConfirmedApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.topupConfirmedURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from top oup api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //  topup automatic insert  Api method
  static Future<CommonSuccessModel?> topUpAutomaticConfirmedApi({
    required Map<String, dynamic> body,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.topUpAutomaticConfirmedURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('err from topup automatic api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Save Recipient all process
  static Future<AllRecipientModel?> allRecipientAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.allRecipientURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        AllRecipientModel modelData = AllRecipientModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from all recipient info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  static Future<RecipientSaveInfoModel?> saveRecipientInfoAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.recipientSaveInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        RecipientSaveInfoModel modelData =
            RecipientSaveInfoModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from save recipient info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  static Future<RecipientDynamicFieldModel?> recipientDynamicFieldAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.recipientDynamicFieldURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        RecipientDynamicFieldModel modelData =
            RecipientDynamicFieldModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from dynamic field recipient info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  static Future<CommonSuccessModel?> recipientStoreApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.recipientStoreURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from recipient stor api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  static Future<CommonSuccessModel?> recipientUpdateApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.recipientUpdateURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from recipient stor api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  static Future<CommonSuccessModel?> recipientDeleteApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.recipientDeleteURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from recipient stor api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  static Future<RecipientEditModel?> recipientEditAPi(
      {required String id}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.recipientEditURL + id,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        RecipientEditModel modelData = RecipientEditModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from edit recipient info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  /// remittance
  static Future<RemittanceInfoModel?> remittanceInfoAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.remittanceInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        RemittanceInfoModel modelData =
            RemittanceInfoModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from info recipient info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  static Future<CommonSuccessModel?> remittanceConfirmAPi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.remittanceConfirmedURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from recipient stor api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  static Future<RemittanceGetRecipientModel?> remittanceGetRecipientAPi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.remittanceGetRecipientURL, body, code: 200);
      if (mapResponse != null) {
        RemittanceGetRecipientModel modelData =
            RemittanceGetRecipientModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from recipient stor api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// transaction
  static Future<TransactionLogModel?> getTransactionLogAPi(
      {String type = ""}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.transactionLogURL + type,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        TransactionLogModel modelData =
            TransactionLogModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from rransaction recipient info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in all recipient info Api');
      return null;
    }
    return null;
  }

  //
  static Future<UpdateKycModel?> getUserKYCInfo() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.userKycURL,
        code: 200,
      );
      if (mapResponse != null) {
        UpdateKycModel basicDataModel = UpdateKycModel.fromJson(mapResponse);

        return basicDataModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from update kyc data Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in basic data Api');
      return null;
    }
    return null;
  }

  /// two Fa Security Api Process

  static Future<TwoFaInfoModel?> getTwoFAInfoAPi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.twoFAInfoURL,
        code: 200,
        showResult: true,
      );
      if (mapResponse != null) {
        TwoFaInfoModel modelData = TwoFaInfoModel.fromJson(mapResponse);

        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from twofa api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in twofa info Api');
      return null;
    }
    return null;
  }

  static Future<CommonSuccessModel?> twoFAVerifyAPi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.twoFAVerifyURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(modelData.message.success.first.toString());
        return modelData;
      }
    } catch (e) {
      log.e('err from twoFAVerify api service ==> $e');
      // CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// make payment
  // card info api
  static Future<MakePaymentInfoModel?> makePaymentInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.makePaymentInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        MakePaymentInfoModel sendMoneyInfoModel =
            MakePaymentInfoModel.fromJson(mapResponse);

        return sendMoneyInfoModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from make payment info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CommonSuccessModel?> checkMerchantExistApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.checkMerchantExistURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from check user exist api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CheckMercantWithQrCodeModel?> checkMerchantWithQrCodeApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.checkMerchantWithQeCodeURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CheckMercantWithQrCodeModel checkUserWithQrCodeModel =
            CheckMercantWithQrCodeModel.fromJson(mapResponse);

        return checkUserWithQrCodeModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from check user with qr code api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CommonSuccessModel?> makePaymentApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.makePaymentURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel sendMoneyModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(sendMoneyModel.message.success.first);
        return sendMoneyModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send money api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // check user sms verify submit
  static Future<CommonSuccessModel?> smsVerifyApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.smsVerifyURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel modelData = CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(modelData.message.success.first);
        return modelData;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send money api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // delete account
  static Future<CommonSuccessModel?> deleteAccountApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.deleteAccountURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel checkUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(checkUserModel.message.success.first.toString());
        return checkUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from delete account service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in delete account ');
      return null;
    }
    return null;
  }

  //! recipient check
  static Future<CheckRecipientModel?> checkRecipientApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.checkRecipientURL,
        body,
        code: 200,
        duration: 25,
      );
      if (mapResponse != null) {
        CheckRecipientModel checkRegisterModel =
            CheckRecipientModel.fromJson(mapResponse);
        CustomSnackBar.success(
            checkRegisterModel.message.success.first.toString());
        return checkRegisterModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from check recipient user service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in check recipient user');
      return null;
    }
    return null;
  }

  // send otp email
  static Future<CommonSuccessModel?> sendForgotOTPEmailApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.sendForgotOTPEmailURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel checkUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(checkUserModel.message.success.first.toString());
        return checkUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send otp email service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in check  checkUserModel');
      return null;
    }
    return null;
  }

  // verify Email User Process
  static Future<CommonSuccessModel?> verifyForgotEmailApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: true).post(
        ApiEndpoint.verifyForgotOTPEmailURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel verifyEmailUserModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(
            verifyEmailUserModel.message.success.first.toString());
        return verifyEmailUserModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from send otp email service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in verify forgot email ');
      return null;
    }
    return null;
  }

//
// ---------------------------------------------------------------------------
//                              Virtual Card Sudo
// ---------------------------------------------------------------------------
//

  // card info api
  static Future<VirtualCardSudoInfoModel?> sudoCardInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.sudoCardInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        VirtualCardSudoInfoModel cardInfoModel =
            VirtualCardSudoInfoModel.fromJson(mapResponse);

        return cardInfoModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from sudo card info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in sudo card info Api');
      return null;
    }
    return null;
  }

  // card details api
  static Future<SudoCardDetailsModel?> sudoCardDetailsApi(
      {required String id}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.sudoCardDetailsURL + id,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        SudoCardDetailsModel cardDetailsModel =
            SudoCardDetailsModel.fromJson(mapResponse);

        return cardDetailsModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card details api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in card details info Api');
      return null;
    }
    return null;
  }

  // card block api
  static Future<CommonSuccessModel?> sudoCardBlockApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sudoCardBlockURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card block api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card block api
  static Future<CommonSuccessModel?> sudoCardUnBlockApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sudoCardUnBlockURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card unblock api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card block api
  static Future<CommonSuccessModel?> sudoAddFundApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sudoAddFundURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card add fund api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in add money info Api');
      return null;
    }
    return null;
  }

  // card make or remove default api
  static Future<CommonSuccessModel?> sudoCardMakeOrRemoveDefaultApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sudoCardMakeOrRemoveDefaultFundURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from card sudo Card Make Or Remove Default Api api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error(
          'Something went Wrong! in sudo Card Make Or Remove Default Api info Api');
      return null;
    }
    return null;
  }

  // card make or remove default api
  static Future<CommonSuccessModel?> flutterWaveCardMakeOrRemoveDefaultApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.flutterWaveCardMakeOrRemoveDefaultFundURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from card flutter wave Card Make Or Remove Default Api api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // card create api
  static Future<CommonSuccessModel?> sudoCreateCardApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.sudoCreateCardURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardAddFundModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardAddFundModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from card create card  api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in create card info Api');
      return null;
    }
    return null;
  }

  // with img update profile api
  static Future<CommonSuccessModel?> updateKYCApi({
    required Map<String, String> body,
    required List<String> pathList,
    required List<String> fieldList,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).multipartMultiFile(
        ApiEndpoint.updateKYCApi,
        body,
        showResult: true,
        fieldList: fieldList,
        pathList: pathList,
      );
      if (mapResponse != null) {
        CommonSuccessModel registrationModel =
            CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(
            registrationModel.message.success.first.toString());
        return registrationModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from update kyc api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in update kyc Model');
      return null;
    }
    return null;
  }

  // ---------------------------------------------------------------------------
//                              Virtual Card Stripe
// ---------------------------------------------------------------------------
//

  //stripe card info api
  static Future<StripeCardInfoModel?> stripeCardInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.stripeCardInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        StripeCardInfoModel cardInfoModel =
            StripeCardInfoModel.fromJson(mapResponse);

        return cardInfoModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from stripe card info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in stripe card info Api');
      return null;
    }
    return null;
  }

  // stripe card details api
  static Future<StripeCardDetailsModel?> stripeCardDetailsApi(String id) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.stripeCardDetailsURL + id,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        StripeCardDetailsModel cardDetailsModel =
            StripeCardDetailsModel.fromJson(mapResponse);

        return cardDetailsModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from stripe card details api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error(
          'Something went Wrong! in stripe card details info Api');
      return null;
    }
    return null;
  }

//stripe card transaction method
  static Future<StripeCardTransactionModel?> stripeCardTransactionApi(
      String cardId) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.stripeCardTransactionURL}$cardId",
        showResult: true,
      );
      if (mapResponse != null) {
        StripeCardTransactionModel cardTransactionModel =
            StripeCardTransactionModel.fromJson(mapResponse);
        // CustomSnackBar.error(
        //     cardTransactionModel.message.success.first.toString());
        return cardTransactionModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from my stripe Card Transaction Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // stripe card sensitive api
  static Future<StripeSensitiveModel?> stripeSensitiveApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.stripeSensitiveURl,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        StripeSensitiveModel cardBlockModel =
            StripeSensitiveModel.fromJson(mapResponse);

        return cardBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from stripe sensitive api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in stripe sensitive Api');
      return null;
    }
    return null;
  }

  //stripe card inactive api
  static Future<CommonSuccessModel?> stripeInactiveApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.stripeInactiveURl,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from inactive api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! inactive Api');
      return null;
    }
    return null;
  }

  // stripe card active api
  static Future<CommonSuccessModel?> stripeActiveApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.stripeActiveURl,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from stripe card active Api api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error(
          'Something went Wrong! in stripe card active  Api info Api');
      return null;
    }
    return null;
  }

  //stripe card buy  Api Method
  static Future<CommonSuccessModel?> stripeBuyCardApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.stripeBuyCardURl,
        body,
        showResult: true,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnblockModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.error(cardUnblockModel.message.success.first.toString());
        return cardUnblockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from stripe card buy api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in stripe card buy Model');
      return null;
    }
    return null;
  }

  // add money tatum gateway

  static Future<TatumGatewayModel?> tatumInsertApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.addMoneySubmitData,
        body,
        code: 200,
        duration: 15,
        showResult: true,
      );
      if (mapResponse != null) {
        TatumGatewayModel result = TatumGatewayModel.fromJson(mapResponse);
        // CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from tatum api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Tatum confirm process
  static Future<CommonSuccessModel?> tatumConfirmApiProcess(
      {required Map<String, dynamic> body, required String url}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(url, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('err from Tatum confirm  api service ==> $e');
      // CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  //>>>>>>>>>>>>> agent money out
  // agent money out
  static Future<AgentMoneyOutInfoModel?> agentMoneyOutInfoApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.agentMoneyOutInfoURL,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        AgentMoneyOutInfoModel agentMoneyOutInfoModel =
            AgentMoneyOutInfoModel.fromJson(mapResponse);

        return agentMoneyOutInfoModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from agent money out info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CommonSuccessModel?> checkAgentExistApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.agentMoneyCheckURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel cardUnBlockModel =
            CommonSuccessModel.fromJson(mapResponse);

        return cardUnBlockModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from check agent exist api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CheckAgentWithQrCodeModel?> checkAgentWithQrCodeApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.qrCodeScanAgentMoneyOutURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CheckAgentWithQrCodeModel checkAgentWithQrCodeModel =
            CheckAgentWithQrCodeModel.fromJson(mapResponse);

        return checkAgentWithQrCodeModel;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from check agent with qr code api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // check user api
  static Future<CommonSuccessModel?> agentMoneyOutConfirmApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.agentMoneyOutConfirm,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel sendMoneyModel =
            CommonSuccessModel.fromJson(mapResponse);
        // CustomSnackBar.success(sendMoneyModel.message.success.first);
        return sendMoneyModel;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from agent money out api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! ');
      return null;
    }
    return null;
  }

  // remaing balance api service
  static Future<RemainingBalanceModel?> remainingBalanceAPi(
    String type,
    String attribute,
    String senderAmount,
    String currencyCode,
    int id,
    double extra,
  ) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.remainingBalanceURL}$type&attribute=$attribute&sender_amount=$senderAmount&currency_code=$currencyCode&charge_id=$id&extra_rate=$extra",
        showResult: true,
      );
      RemainingBalanceModel remainingBalanceModel =
          RemainingBalanceModel.fromJson(mapResponse!);

      return remainingBalanceModel;
    } catch (e) {
      log.e('🐞🐞🐞 err from remaining balance Api service ==> $e 🐞🐞🐞');
      // CustomSnackBar.error('Something went Wrong!');
      return null;
    }
  }
}
