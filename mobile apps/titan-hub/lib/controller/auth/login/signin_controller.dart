import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/common/common_success_model.dart';

import '../../../backend/local_storage/local_storage.dart';
import '../../../backend/model/auth/login/login_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../routes/routes.dart';

class SignInController extends GetxController {
  final emailController = TextEditingController();
  final emailForgotController = TextEditingController();
  final passwordController = TextEditingController();

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  RxString countryCode = LocalStorages.getCountryCode()!.obs;
  RxString verify = ''.obs;
  RxInt resendAuthToken = 0.obs;
  RxInt twoFaStatus = 0.obs;
  RxInt twoFaVerified = 0.obs;
  RxBool isEmailVerification = true.obs;

  final _isSendOTPLoading = false.obs;

  bool get isSendOTPLoading => _isSendOTPLoading.value;

  //! Send OTP Email Process
  late CommonSuccessModel _sendOTPEmailModel;

  CommonSuccessModel get sendOTPEmailModel => _sendOTPEmailModel;

  Future<CommonSuccessModel> sendOTPEmailProcess() async {
    _isSendOTPLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {};
    // calling login api from api service
    await ApiServices.sendOTPEmailApi(body: inputBody).then((value) {
      _sendOTPEmailModel = value!;

      _isSendOTPLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isSendOTPLoading.value = false;
    update();
    return _sendOTPEmailModel;
  }

  //!loading api
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late LoginModel _loginModel;

  LoginModel get loginModel => _loginModel;

  // Login process function
  Future<LoginModel> loginProcess() async {
    _isLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'email': emailController.text,
      'password': passwordController.text,
    };
    // calling login api from api service
    await ApiServices.loginApi(body: inputBody).then((value) {
      _loginModel = value!;

      debugPrint("Email Verified => Login Process");

      twoFaStatus.value = _loginModel.data.user.twoFactorStatus;
      twoFaVerified.value = _loginModel.data.user.twoFactorVerified;

      if (_loginModel.data.user.emailVerified == 0) {
        isEmailVerification.value = false;

        LocalStorages.saveToken(token: loginModel.data.token.toString());

        _goToEmailVerification(_loginModel);
      } else {
        _goToSavedUser(_loginModel);

        /// this is for 2fa check
        if (twoFaStatus.value == 1 && twoFaVerified.value == 0) {
          Get.toNamed(Routes.otp2FaScreen);
        } else {
          LocalStorages.isLoginSuccess(isLoggedIn: true);
          LocalStorages.isLoggedIn();
          update();
          Get.offAllNamed(Routes.bottomNavBarScreen);
        }
      }

      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _loginModel;
  }

  final _isLoading2 = false.obs;

  bool get isLoading2 => _isLoading2.value;

  //! Forget Password Email Process
  late CommonSuccessModel _verifyEmailModel;

  CommonSuccessModel get verifyEmailModel => _verifyEmailModel;

  // Verify email process function
  Future<CommonSuccessModel> verifyEmailProcess(
      {required String otpCode}) async {
    _isLoading2.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'code': otpCode,
    };

    await ApiServices.verifyEmailApi(body: inputBody).then((value) {
      _verifyEmailModel = value!;

      _isLoading2.value = false;
      update();

      /// this is for 2fa check
      if (twoFaStatus.value == 1 && twoFaVerified.value == 0) {
        debugPrint("1 : 0");
        debugPrint("1 : 0");
        debugPrint("1 : 0");
        debugPrint("1 : 0");
        Get.toNamed(Routes.otp2FaScreen);
      } else {
        LocalStorages.isLoginSuccess(isLoggedIn: true);
        LocalStorages.isLoggedIn();
        update();
        Get.offAllNamed(Routes.bottomNavBarScreen);
      }
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading2.value = false;
    update();
    return _verifyEmailModel;
  }

  final _isSendForgotOTPLoading = false.obs;

  bool get isSendForgotOTPLoading => _isSendForgotOTPLoading.value;

  //! Send OTP Email Process
  late CommonSuccessModel _sendForgotOTPEmailModel;

  CommonSuccessModel get sendForgotOTPEmailModel => _sendForgotOTPEmailModel;

  Future<CommonSuccessModel> sendForgotOTPEmailProcess() async {
    _isSendForgotOTPLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {'email': emailForgotController.text};

    await ApiServices.sendForgotOTPEmailApi(body: inputBody).then((value) {
      _sendForgotOTPEmailModel = value!;
      Get.toNamed(Routes.resetOtpScreen);
      _isSendForgotOTPLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isSendForgotOTPLoading.value = false;
    update();
    return _sendForgotOTPEmailModel;
  }

  //! Forget Password Email Process
  late CommonSuccessModel _verifyForgotEmailModel;

  CommonSuccessModel get verifyForgotEmailModel => _verifyForgotEmailModel;

  // Verify email process function
  Future<CommonSuccessModel> verifyForgotEmailProcess(
      {required String otpCode}) async {
    _isLoading2.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'code': otpCode,
      'email': emailForgotController.text,
    };

    await ApiServices.verifyForgotEmailApi(body: inputBody).then((value) {
      _verifyForgotEmailModel = value!;
      Get.offAllNamed(Routes.resetPasswordScreen, arguments: {
        'otp': otpCode,
        'email': emailForgotController.text,
      });
      _isLoading2.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading2.value = false;
    update();
    return _verifyForgotEmailModel;
  }

  RxBool isVerifyCode = false.obs;
  RxBool isSMSVerification = true.obs;

  void _goToSavedUser(LoginModel loginModel) {
    LocalStorages.saveToken(token: loginModel.data.token.toString());
    LocalStorages.saveId(id: loginModel.data.user.id.toString());
  }

  void _goToEmailVerification(LoginModel loginModel) {
    sendOTPEmailProcess();
    Get.toNamed(Routes.emailVerificationScreen);
  }

  goToForgotEmailVerification() {
    sendForgotOTPEmailProcess();
  }

  void onPressedSignUP() {
    Get.toNamed(Routes.registrationScreen);
  }
}
