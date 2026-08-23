import 'package:get/get.dart';

import '../../backend/model/gift_card/my_gift_card_model.dart';
import '../../backend/services/api_services.dart';
import '../../backend/services/gift_card_api_services.dart';

class GiftCardController extends GetxController {
  @override
  void onInit() {
    getMyCardInfoApi();
    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late MyGiftCardModel _myGiftCardModel;
  MyGiftCardModel get myGiftCardModel => _myGiftCardModel;

  Future<MyGiftCardModel> getMyCardInfoApi() async {
    _isLoading.value = true;
    update();
    await GiftCardApiServices.myGiftCardInfoProcess().then((value) {
      _myGiftCardModel = value!;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isLoading.value = false;
    update();

    return _myGiftCardModel;
  }
}
