import '../model/common/common_success_model.dart';
import '../model/request_money/check_user_qr_scan.dart';
import '../model/request_money/request_money_info_model.dart';
import '../model/request_money/request_money_log_model.dart';
import '../utils/api_method.dart';
import '../utils/custom_snackbar.dart';
import '../utils/logger.dart';
import 'api_endpoint.dart';

final log = logger(RequestMoneyApiServices);

mixin RequestMoneyApiServices {
  ///* Get request money info process api
  Future<RequestMoneyInfoModel?> getRequestMoneyInfoProcessApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.requestMoneyInfoURL,
        code: 200,
      );
      if (mapResponse != null) {
        RequestMoneyInfoModel result =
            RequestMoneyInfoModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from  Get request money info process api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Check user by mail
  Future<CommonSuccessModel?> checkUserExistApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.requestMoneyCheckUserURL,
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
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// check user using qr code
  Future<CheckUserQrCodeModel?> checkUserWithQrCodeApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.requestMoneyQrScanURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CheckUserQrCodeModel result =
            CheckUserQrCodeModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from check user with qr code api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Request money process api
  Future<CommonSuccessModel?> requestMoneySubmitURL(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.requestMoneySubmitURL,
        body,
        code: 200,
      );
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('err from post Request money process Api service ==> $e');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Request money log api
  Future<RequestMoneyLogModel?> getRequestMoneyLogApi() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.requestMoneyLogsURL,
        code: 200,
      );
      if (mapResponse != null) {
        RequestMoneyLogModel result =
            RequestMoneyLogModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from  Get request money info process api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Request money log reject api
  Future<CommonSuccessModel?> rejectRequestMoneyApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.requestMoneyLogsRejectURL,
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
          '🐞🐞🐞 err from Request money log reject api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  /// Request money log approve api
  Future<CommonSuccessModel?> approveRequestMoneyApi(
      {required Map<String, dynamic> body}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.requestMoneyLogsApproveURL,
        body,
        code: 200,
        showResult: false,
      );
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first);
        return result;
      }
    } catch (e) {
      log.e(
          '🐞🐞🐞 err from Request money log approve api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }
}
