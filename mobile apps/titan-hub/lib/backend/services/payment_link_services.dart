import 'package:get_storage/get_storage.dart';

import '../model/common/common_success_model.dart';
import '../model/payment_link/payment_edit_link_model.dart';
import '../model/payment_link/payment_link_model.dart';
import '../model/payment_link/payment_link_store_model.dart';
import '../model/payment_link/payment_update_model.dart';
import '../utils/api_method.dart';
import '../utils/custom_snackbar.dart';
import '../utils/logger.dart';
import 'api_endpoint.dart';

final log = logger(PaymentLinkApiServices);

mixin PaymentLinkApiServices {
  ///* get Payment Link process api
  Future<PaymentLinkModel?> getPaymentLinkProcessApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.paymentLinkGetURL,
        code: 200,
      );
      if (mapResponse != null) {
        PaymentLinkModel result = PaymentLinkModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from  Get Payment Link process api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in Payment Link Model');
      return null;
    }
    return null;
  }

  ///* post paymentLinkStore  without img process api
  Future<PaymentLinkStoreModel?> paymentLinkStoreWithoutImageApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.paymentLinkStoreURL, body, code: 200);
      if (mapResponse != null) {
        PaymentLinkStoreModel result =
            PaymentLinkStoreModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e(
          'err from post PaymentLink Process without image Api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  ///* post  paymentLinkStore with img process api
  Future<PaymentLinkStoreModel?> paymentLinkStoreWithImageApi(
      {required Map<String, String> body, required dynamic filepath}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).multipart(
          ApiEndpoint.paymentLinkStoreURL, body, filepath, 'image',
          code: 200);

      if (mapResponse != null) {
        PaymentLinkStoreModel paymentLinkStoreModel =
            PaymentLinkStoreModel.fromJson(mapResponse);
        CustomSnackBar.success(
            paymentLinkStoreModel.message.success.first.toString());
        return paymentLinkStoreModel;
      }
    } catch (e) {
      log.e('err from  payment Link Store with image api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  ///******************************* payment log status update api ************************************///

  ///* post payment log status update api
  Future<CommonSuccessModel?> updatePaymentLinkStatusApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.statusURL, body, code: 200);
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('err from Status Update Api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  ///******************************* payment log edit related apis ************************************///

  ///* get Payment Link Edit api
  Future<PaymentLinkEditModel?> getPaymentEditLinkProcessApi(int target) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.paymentLinkEditGetURL}$target&&lang=${GetStorage().read('selectedLanguage')}",
        code: 200,
      );
      if (mapResponse != null) {
        PaymentLinkEditModel result =
            PaymentLinkEditModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from  Get Payment Link Edit process api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong! in Payment Link Edit Model');
      return null;
    }
    return null;
  }

  ///* post  payment Link update with img process api

  Future<PaymentLinkUpdateModel?> updatePaymentWithImageApi(
      {required Map<String, String> body, required dynamic filepath}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).multipart(
          ApiEndpoint.paymentLinkUpdateURL, body, filepath, 'image',
          code: 200);

      if (mapResponse != null) {
        PaymentLinkUpdateModel paymentLinkUpdateModel =
            PaymentLinkUpdateModel.fromJson(mapResponse);
        CustomSnackBar.success(
            paymentLinkUpdateModel.message.success.first.toString());
        return paymentLinkUpdateModel;
      }
    } catch (e) {
      log.e('err from  payment Link Update with image api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  ///* post payment Link update without image api
  Future<PaymentLinkUpdateModel?> updatePaymentWithoutImageApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(ApiEndpoint.paymentLinkUpdateURL, body, code: 200);
      if (mapResponse != null) {
        PaymentLinkUpdateModel result =
            PaymentLinkUpdateModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('err from payment Link Update Api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }
}
