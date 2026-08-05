import '../../../../backend/model/categories/virtual_card/strowallet_models/strowallet_details_controller.dart';
import '../../../../backend/model/common/common_success_model.dart';
import '../../../../backend/services/ strowallet_api_services.dart';
import '../../../../backend/services/api_services.dart';
import '../../../../utils/basic_screen_imports.dart';
import 'strowallelt_info_controller.dart';

class StrowalletCardDetailsController extends GetxController {
  RxBool isSelected = false.obs;
  RxBool isShowSensitive = false.obs;
  RxString cardPlan = "".obs;
  RxString cardCVC = "".obs;
  final strowalletController = Get.put(VirtualStrowalletCardController());
  @override
  void onInit() {
    getCardDetailsData();
    super.onInit();
  }

  ///>>>>>>>>>> get details data
  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  final _isCardStatusLoading = false.obs;
  bool get isCardStatusLoading => _isCardStatusLoading.value;
  // Card Details Method
  late StrowalletCardDetailsModel _stripeCardDetailsModel;
  StrowalletCardDetailsModel get cardDetailsModel => _stripeCardDetailsModel;

  Future<StrowalletCardDetailsModel> getCardDetailsData() async {
    _isLoading.value = true;
    update();

    await StrowalletApiServices.strowalletCardDetailsApi(
            strowalletController.strowalletCardId.value)
        .then((value) {
      _stripeCardDetailsModel = value!;

      if (_stripeCardDetailsModel.data.myCards.status == true) {
        isSelected.value = true;
      } else {
        isSelected.value = false;
      }

      update();
    }).catchError((onError) {
      log.e(onError);
      _isLoading.value = false;
      update();
    });

    _isLoading.value = false;
    update();
    return _stripeCardDetailsModel;
  }

  /// >>>>>>>>>>>> active card
  late CommonSuccessModel _cardActiveModel;
  CommonSuccessModel get cardActiveModel => _cardActiveModel;

  Future<CommonSuccessModel> cardActiveApi() async {
    _isCardStatusLoading.value = true;
    update();
    Map<String, dynamic> inputBody = {
      'card_id': strowalletController.strowalletCardId.value,
    };
    await StrowalletApiServices.strowalletpeActiveApi(body: inputBody)
        .then((value) {
      _cardActiveModel = value!;
      update();
      debugPrint('Card Active');
      getCardDetailsData();
    }).catchError((onError) {
      log.e(onError);
      _isCardStatusLoading.value = false;
      update();
    });

    _isCardStatusLoading.value = false;
    update();
    return _cardActiveModel;
  }

  ///>>>>>>>>>>>>>  card inactive process
  late CommonSuccessModel _cardInactiveModel;
  CommonSuccessModel get cardInactiveModel => _cardInactiveModel;

  Future<CommonSuccessModel> cardInactiveApi() async {
    _isCardStatusLoading.value = true;
    update();
    Map<String, dynamic> inputBody = {
      'card_id': strowalletController.strowalletCardId.value,
    };
    await StrowalletApiServices.strowalletInactiveApi(body: inputBody)
        .then((value) {
      _cardInactiveModel = value!;
      update();
      debugPrint('Card Inactive');
      getCardDetailsData();
    }).catchError((onError) {
      log.e(onError);
      _isCardStatusLoading.value = false;
      update();
    });

    _isCardStatusLoading.value = false;
    update();
    return _cardInactiveModel;
  }

  _cardToggle() {
    if (_stripeCardDetailsModel.data.myCards.status == true) {
      cardInactiveApi();
    } else {
      cardActiveApi();
    }
  }

  get cardToggle => _cardToggle();
}
