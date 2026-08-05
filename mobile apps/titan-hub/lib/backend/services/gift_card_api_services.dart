import '../model/common/common_success_model.dart';
import '../model/gift_card/gift_card_list_model.dart';
import '../model/gift_card/gift_details_model.dart';
import '../model/gift_card/my_gift_card_model.dart';
import '../utils/api_method.dart';
import '../utils/custom_snackbar.dart';
import 'api_endpoint.dart';

class GiftCardApiServices {
  // my gift card info api
  static Future<MyGiftCardModel?> myGiftCardInfoProcess() async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        ApiEndpoint.myGiftCardURL,
        code: 200,
        showResult: false,
      );
      MyGiftCardModel result = MyGiftCardModel.fromJson(mapResponse!);
      return result;
    } catch (e) {
      log.e('🐞🐞🐞 err from my gift card info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
  }

  // All gift card  api
  static Future<GiftCardListModel?> allGiftCardInfoProcess(
      String page, String countryCode) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        countryCode == ''
            ? "${ApiEndpoint.allGiftCardURL}?page=$page"
            : "${ApiEndpoint.allGiftCardURL}?page=$page&country=$countryCode",
      );
      if (mapResponse != null) {
        GiftCardListModel result = GiftCardListModel.fromJson(mapResponse);
        // CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e(' err from GiftCardListModel api service ==> $e ');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }

  // gift card details info api
  static Future<GiftCardDetailsModel?> getGiftCardDetailsApi(
    String productId,
  ) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.giftCardDetailsURL}$productId",
        code: 200,
        showResult: false,
      );
      GiftCardDetailsModel result = GiftCardDetailsModel.fromJson(mapResponse!);
      return result;
    } catch (e) {
      log.e('🐞🐞🐞 err from gift card details info api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
  }

  // Card Create Api Method
  static Future<CommonSuccessModel?> createGiftCardApi({
    required Map<String, dynamic> body,
  }) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).post(
        ApiEndpoint.giftCardOrderURL,
        body,
        code: 200,
        showResult: true,
      );
      CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse!);
      CustomSnackBar.success(result.message.success.first.toString());
      return result;
    } catch (e) {
      log.e('🐞🐞🐞 err from create gift card api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
  }
}
