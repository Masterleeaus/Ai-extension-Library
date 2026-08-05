import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/auth/registation/check_register_user_model.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/local_storage/local_storage.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../backend/utils/logger.dart';

final log = logger(RegistrationController);

class RegistrationController extends GetxController {
  final emailController = TextEditingController();
  final phoneController = TextEditingController();

  @override
  void dispose() {
    emailController.dispose();

    super.dispose();
  }

  RxString countryCode = "".obs;

  @override
  void onInit() {
    countryCode.value = LocalStorages.getCountryCode()!;

    super.onInit();
  }

  RxString verify = ''.obs;
  RxInt resendAuthToken = 0.obs;

  void onTapContinue() {
    Get.toNamed(Routes.emailOtpScreen);
  }
             
  void onPressedSignIn() { 
    Get.toNamed(Routes.signInScreen);
  }
                  
                        
  File? fronImage;      
  File? backImage;
                   
                                            
  //end kyc form                                              
            
  final _isLoading = false.obs;             
                                
  bool get isLoading => _isLoading.value;                                                                                   
       
  late CheckRegisterUserModel _checkRegisterUserModel;
                                                                            
  CheckRegisterUserModel get checkRegisterUserModel => _checkRegisterUserModel;
  
  // check Exist User Process process function
  Future<CheckRegisterUserModel> checkExistUserProcess() async {
    _isLoading.value = true;
    update(); 
 
    debugPrint(countryCode.value.toString());
    Map<String, dynamic> inputBody = {
      'email': emailController.text,
    }; 
  
  
  
    // check Exist User api from api service
    await ApiServices.checkRegisterApi(body: inputBody).then((value) {
      _checkRegisterUserModel = value!;
      if (LocalStorages.isEmailVerification()) {
        Get.toNamed(Routes.emailOtpScreen);
        sendOTPEmailProcess(email: emailController.text);
      } else {
        // LocalStorages.saveId(
        //   id: _checkRegisterUserModel.data.user.id.toString(),
        // );
        Get.toNamed(Routes.kycFromScreen);
      }

      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _checkRegisterUserModel;
  }

//   goToPhoneVerification() async {
//     _isLoading.value = true;
//     try {
//       String code = countryCode.value[0] == "+" ? countryCode.value : "+${countryCode.value}";
//
//       await FirebaseAuth.instance.verifyPhoneNumber(
//         phoneNumber: '$code${numberController.text}',
//         verificationCompleted: (PhoneAuthCredential credential) {},
//         verificationFailed: (FirebaseAuthException e) {
//           log.e(e);
//
//           if (e.code == 'invalid-phone-number') {
//             log.e(
//                 'The provided phone number "${'+${countryCode.value}${numberController.text}'}" is not valid.');
//             CustomSnackBar.error(
//                 'The provided phone number "${'+${countryCode.value}${numberController.text}'}" is not valid.');
//           }
//           _isLoading.value = false;
//         },
//         codeSent: (String verificationId, int? resendToken) {
//           _isLoading.value = false;
//           verify.value = verificationId;
//           resendAuthToken.value = resendToken!;
//           if (verify.isNotEmpty) {
//             Get.toNamed(Routes.emailOtpScreen);
//           } else {
//             CustomSnackBar.error('OTP Sent Error Resend OTP Again');
//           }
//         },
//         codeAutoRetrievalTimeout: (String verificationId) {},
//       );
//     } catch (e) {
//       log.e(e);
//
//       log.e('OTP Sent Error Resend OTP Again');
//       CustomSnackBar.error('OTP Sent Error Resend OTP Again');
//     }
//
//     update();
//   }

//!  reesend otp

  final isVerifyCode = false.obs;

  // resentOtpFirebase(context) async {
  //   isVerifyCode.value = true;
  //   try {
  //     String code = countryCode.value[0] == "+" ? countryCode.value : "+${countryCode.value}";
  //
  //     await FirebaseAuth.instance.verifyPhoneNumber(
  //       phoneNumber: '$code${numberController.text}',
  //       verificationCompleted: (PhoneAuthCredential credential) {},
  //       verificationFailed: (FirebaseAuthException e) {
  //         isVerifyCode.value = false;
  //         if (e.code == 'invalid-phone-number') {}
  //       },
  //       codeSent: (String verificationId, int? resendToken) {
  //         isVerifyCode.value = false;
  //         resendAuthToken.value = resendToken!.toInt();
  //       },
  //       forceResendingToken: resendAuthToken.value,
  //       codeAutoRetrievalTimeout: (String verificationId) {},
  //     );
  //   } catch (e) {
  //     debugPrint('OTP Sent Error Resend OTP Again');
  //   }
  // }

  //

  final _isSendOTPLoading = false.obs;

  bool get isSendOTPLoading => _isSendOTPLoading.value;

  //! Send OTP Email Process
  late CommonSuccessModel _sendOTPEmailModel;

  CommonSuccessModel get sendOTPEmailModel => _sendOTPEmailModel;

  Future<CommonSuccessModel> sendOTPEmailProcess(
      {required String email}) async {
    _isSendOTPLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'email': email,
      'agree': '1',
    };

    await ApiServices.sendRegisterOTPEmailApi(body: inputBody).then((value) {
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

  final _isLoading2 = false.obs;

  bool get isLoading2 => _isLoading2.value;

  late CommonSuccessModel _verifyEmailModel;

  CommonSuccessModel get verifyEmailModel => _verifyEmailModel;

  // Verify email process function
  Future<CommonSuccessModel> verifyEmailProcess(
      {required String otpCode}) async {
    _isLoading2.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'email': emailController.text,
      'code': otpCode,
    };

    await ApiServices.verifyRegisterEmailApi(body: inputBody).then((value) {
      _verifyEmailModel = value!;
      _isLoading2.value = false;

      update();
      Get.toNamed(Routes.kycFromScreen);
    }).catchError((onError) {
      _isLoading2.value = false;
      log.e(onError);
    });

    _isLoading2.value = false;
    update();
    return _verifyEmailModel;
  }
}
